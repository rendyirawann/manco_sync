use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    Json,
};
use serde::{Deserialize, Serialize};
use uuid::Uuid;
use chrono::Utc;
use mongodb::bson::{doc, Document};
use redis::AsyncCommands;
use std::sync::Arc;

use crate::models::{
    ApiResponse, Manga, Chapter, Genre, PageDetail, ChapterPagesDoc,
    CreateMangaPayload, SyncChapterPagesPayload
};

#[derive(Clone)]
pub struct AppState {
    pub pg_pool: sqlx::PgPool,
    pub mongo_client: mongodb::Client,
    pub redis_client: redis::Client,
}

// --- Query Parameters for List Manga ---
#[derive(Deserialize, Debug)]
pub struct MangaQuery {
    pub page: Option<i64>,
    pub limit: Option<i64>,
    pub search: Option<String>,
    pub genre: Option<String>, // Genre slug
    pub r#type: Option<String>,  // manga, manhwa, manhua
    pub sort: Option<String>,   // latest, popular, rating
}

// --- REST API Handlers ---

/// 1. GET /api/mangas - List all manga (paginated, filtered, cached)
pub async fn list_mangas(
    Query(q): Query<MangaQuery>,
    State(state): State<Arc<AppState>>,
) -> Result<Json<ApiResponse<Vec<Manga>>>, (StatusCode, Json<ApiResponse<String>>)> {
    let page = q.page.unwrap_or(1);
    let limit = q.limit.unwrap_or(20);
    let offset = (page - 1) * limit;

    let search = q.search.unwrap_or_default();
    let genre_slug = q.genre.unwrap_or_default();
    let manga_type = q.r#type.unwrap_or_default();
    let sort = q.sort.unwrap_or_else(|| "latest".to_string());

    // Redis Cache Check (Only cache the first page default queries to save memory)
    let is_cacheable = search.is_empty() && genre_slug.is_empty() && manga_type.is_empty() && page == 1;
    let cache_key = format!("manco:manga:list:{}", sort);

    if is_cacheable {
        if let Ok(mut conn) = state.redis_client.get_async_connection().await {
            if let Ok(cached_data) = conn.get::<_, String>(&cache_key).await {
                if let Ok(decoded_mangas) = serde_json::from_str::<Vec<Manga>>(&cached_data) {
                    return Ok(Json(ApiResponse {
                        success: true,
                        message: "Fetched from Redis Cache".to_string(),
                        data: Some(decoded_mangas),
                    }));
                }
            }
        }
    }

    // Dynamic SQL Query construction
    let mut query_str = String::from(
        "SELECT DISTINCT m.id, m.title, m.slug, m.description, m.cover_image, m.status, 
                m.type, m.author, m.artist, m.release_year, m.rating::DOUBLE PRECISION as rating, 
                m.views_count, m.created_at, m.updated_at 
         FROM mangas m"
    );

    // Join genre pivot table if filtering by genre
    if !genre_slug.is_empty() {
        query_str.push_str(
            " JOIN manga_genre mg ON m.id = mg.manga_id 
             JOIN genres g ON mg.genre_id = g.id"
        );
    }

    query_str.push_str(" WHERE 1=1");

    // Add search filter
    if !search.is_empty() {
        query_str.push_str(" AND m.title ILIKE $1");
    } else {
        query_str.push_str(" AND ($1 = '' OR 1=1)"); // Keep parameter index matching
    }

    // Add type filter
    if !manga_type.is_empty() {
        query_str.push_str(" AND m.type = $2");
    } else {
        query_str.push_str(" AND ($2 = '' OR 1=1)");
    }

    // Add genre filter
    if !genre_slug.is_empty() {
        query_str.push_str(" AND g.slug = $3");
    } else {
        query_str.push_str(" AND ($3 = '' OR 1=1)");
    }

    // Sorting logic
    match sort.as_str() {
        "popular" => query_str.push_str(" ORDER BY m.views_count DESC"),
        "rating" => query_str.push_str(" ORDER BY m.rating DESC NULLS LAST"),
        _ => query_str.push_str(" ORDER BY m.created_at DESC"),
    }

    query_str.push_str(" LIMIT $4 OFFSET $5");

    let search_pattern = format!("%{}%", search);

    // Execute PostgreSQL query
    let mangas = sqlx::query_as::<_, Manga>(&query_str)
        .bind(search_pattern)
        .bind(manga_type)
        .bind(genre_slug)
        .bind(limit)
        .bind(offset)
        .fetch_all(&state.pg_pool)
        .await
        .map_err(|e| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json(ApiResponse {
                    success: false,
                    message: format!("Database Error: {}", e),
                    data: None,
                }),
            )
        })?;

    // Cache list results if cacheable
    if is_cacheable && !mangas.is_empty() {
        if let Ok(mut conn) = state.redis_client.get_async_connection().await {
            if let Ok(serialized) = serde_json::to_string(&mangas) {
                // Cache for 5 minutes
                let _: Result<(), _> = conn.set_ex(&cache_key, serialized, 300).await;
            }
        }
    }

    Ok(Json(ApiResponse {
        success: true,
        message: "Manga list fetched successfully".to_string(),
        data: Some(mangas),
    }))
}

/// 2. GET /api/mangas/:slug - Get Manga Details with Chapters
#[derive(Serialize, Deserialize)]
pub struct MangaDetail {
    pub manga: Manga,
    pub chapters: Vec<Chapter>,
    pub genres: Vec<Genre>,
}

pub async fn get_manga_detail(
    Path(slug): Path<String>,
    State(state): State<Arc<AppState>>,
) -> Result<Json<ApiResponse<MangaDetail>>, (StatusCode, Json<ApiResponse<String>>)> {
    let cache_key = format!("manco:manga:detail:{}", slug);

    // Redis Cache Check
    if let Ok(mut conn) = state.redis_client.get_async_connection().await {
        if let Ok(cached_data) = conn.get::<_, String>(&cache_key).await {
            if let Ok(decoded_detail) = serde_json::from_str::<MangaDetail>(&cached_data) {
                return Ok(Json(ApiResponse {
                    success: true,
                    message: "Fetched detail from Redis Cache".to_string(),
                    data: Some(decoded_detail),
                }));
            }
        }
    }

    // Fetch Manga Metadata from Postgres
    let manga = sqlx::query_as::<_, Manga>(
        "SELECT id, title, slug, description, cover_image, status, type, author, 
                artist, release_year, rating::DOUBLE PRECISION as rating, views_count, 
                created_at, updated_at 
         FROM mangas 
         WHERE slug = $1"
    )
    .bind(&slug)
    .fetch_optional(&state.pg_pool)
    .await
    .map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ApiResponse {
                success: false,
                message: format!("Database Error: {}", e),
                data: None,
            }),
        )
    })?
    .ok_or_else(|| {
        (
            StatusCode::NOT_FOUND,
            Json(ApiResponse {
                success: false,
                message: "Manga not found".to_string(),
                data: None,
            }),
        )
    })?;

    // Fetch Chapters
    let chapters = sqlx::query_as::<_, Chapter>(
        "SELECT id, manga_id, chapter_number::DOUBLE PRECISION as chapter_number, 
                title, slug, views_count, created_at, updated_at 
         FROM chapters 
         WHERE manga_id = $1 
         ORDER BY chapter_number DESC"
    )
    .bind(manga.id)
    .fetch_all(&state.pg_pool)
    .await
    .map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ApiResponse {
                success: false,
                message: format!("Failed to fetch chapters: {}", e),
                data: None,
            }),
        )
    })?;

    // Fetch Genres
    let genres = sqlx::query_as::<_, Genre>(
        "SELECT g.id, g.name, g.slug 
         FROM genres g
         JOIN manga_genre mg ON g.id = mg.genre_id
         WHERE mg.manga_id = $1"
    )
    .bind(manga.id)
    .fetch_all(&state.pg_pool)
    .await
    .map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ApiResponse {
                success: false,
                message: format!("Failed to fetch genres: {}", e),
                data: None,
            }),
        )
    })?;

    let response_data = MangaDetail {
        manga,
        chapters,
        genres,
    };

    // Cache the detail for 30 minutes
    if let Ok(mut conn) = state.redis_client.get_async_connection().await {
        if let Ok(serialized) = serde_json::to_string(&response_data) {
            let _: Result<(), _> = conn.set_ex(&cache_key, serialized, 1800).await;
        }
    }

    Ok(Json(ApiResponse {
        success: true,
        message: "Manga details fetched successfully".to_string(),
        data: Some(response_data),
    }))
}

/// 3. GET /api/chapters/:id/pages - Get Chapter Pages (MongoDB + Redis cache)
pub async fn get_chapter_pages(
    Path(chapter_id): Path<String>,
    State(state): State<Arc<AppState>>,
) -> Result<Json<ApiResponse<Vec<PageDetail>>>, (StatusCode, Json<ApiResponse<String>>)> {
    let cache_key = format!("manco:chapter:pages:{}", chapter_id);

    // 1. Redis Cache Check
    if let Ok(mut conn) = state.redis_client.get_async_connection().await {
        if let Ok(cached_data) = conn.get::<_, String>(&cache_key).await {
            if let Ok(decoded_pages) = serde_json::from_str::<Vec<PageDetail>>(&cached_data) {
                // Async increment views in background using Tokio spawn to be lightning-fast!
                let pool = state.pg_pool.clone();
                let cid = chapter_id.clone();
                tokio::spawn(async move {
                    if let Ok(uuid) = Uuid::parse_str(&cid) {
                        let _ = sqlx::query("UPDATE chapters SET views_count = views_count + 1 WHERE id = $1")
                            .bind(uuid)
                            .execute(&pool)
                            .await;
                    }
                });

                return Ok(Json(ApiResponse {
                    success: true,
                    message: "Fetched pages from Redis Cache".to_string(),
                    data: Some(decoded_pages),
                }));
            }
        }
    }

    // 2. Fetch Pages from MongoDB NoSQL Database
    let db = state.mongo_client.database("manco_sync");
    let collection = db.collection::<Document>("chapter_pages");

    let filter = doc! { "chapter_id": &chapter_id };
    let doc_opt = collection.find_one(filter, None).await.map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ApiResponse {
                success: false,
                message: format!("MongoDB Error: {}", e),
                data: None,
            }),
        )
    })?;

    let pages = if let Some(doc) = doc_opt {
        // Parse dynamic BSON arrays to PageDetail
        if let Ok(pages_bson) = doc.get_array("pages") {
            pages_bson.iter().filter_map(|b| {
                let p_doc = b.as_document()?;
                Some(PageDetail {
                    page_number: p_doc.get_i32("page_number").ok()?,
                    image_url: p_doc.get_str("image_url").ok()?.to_string(),
                    width: p_doc.get_i32("width").ok(),
                    height: p_doc.get_i32("height").ok(),
                })
            }).collect::<Vec<PageDetail>>()
        } else {
            Vec::new()
        }
    } else {
        Vec::new()
    };

    if pages.is_empty() {
        return Err((
            StatusCode::NOT_FOUND,
            Json(ApiResponse {
                success: false,
                message: "No pages found for this chapter".to_string(),
                data: None,
            }),
        ));
    }

    // 3. Cache pages list to Redis (2 hours expiration)
    if let Ok(mut conn) = state.redis_client.get_async_connection().await {
        if let Ok(serialized) = serde_json::to_string(&pages) {
            let _: Result<(), _> = conn.set_ex(&cache_key, serialized, 7200).await;
        }
    }

    // Async increment views in background
    let pool = state.pg_pool.clone();
    let cid = chapter_id.clone();
    tokio::spawn(async move {
        if let Ok(uuid) = Uuid::parse_str(&cid) {
            let _ = sqlx::query("UPDATE chapters SET views_count = views_count + 1 WHERE id = $1")
                .bind(uuid)
                .execute(&pool)
                .await;
            
            // Also increment views on the manga
            let _ = sqlx::query(
                "UPDATE mangas SET views_count = views_count + 1 
                 WHERE id = (SELECT manga_id FROM chapters WHERE id = $1)"
            )
            .bind(uuid)
            .execute(&pool)
            .await;
        }
    });

    Ok(Json(ApiResponse {
        success: true,
        message: "Chapter pages loaded successfully".to_string(),
        data: Some(pages),
    }))
}

/// 4. POST /api/chapters/:id/pages - Sync Pages into MongoDB (From Laravel/Scrapers)
pub async fn sync_chapter_pages(
    Path(chapter_id): Path<String>,
    State(state): State<Arc<AppState>>,
    Json(payload): Json<SyncChapterPagesPayload>,
) -> Result<Json<ApiResponse<String>>, (StatusCode, Json<ApiResponse<String>>)> {
    let db = state.mongo_client.database("manco_sync");
    let collection = db.collection::<Document>("chapter_pages");

    // Convert vector of image paths to PageDetail MongoDB documents
    let pages_bson: Vec<Document> = payload.pages.into_iter().enumerate().map(|(idx, url)| {
        doc! {
            "page_number": (idx + 1) as i32,
            "image_url": url,
            "width": 1200, // Standard default width
            "height": 1600 // Standard default height
        }
    }).collect();

    let filter = doc! { "chapter_id": &chapter_id };
    
    // Upsert into MongoDB
    let update = doc! {
        "$set": {
            "pages": pages_bson,
            "synced_at": mongodb::bson::DateTime::now()
        }
    };

    let options = mongodb::options::UpdateOptions::builder()
        .upsert(true)
        .build();

    collection.update_one(filter, update, options).await.map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ApiResponse {
                success: false,
                message: format!("Failed to save pages to NoSQL: {}", e),
                data: None,
            }),
        )
    })?;

    // Invalidate Redis cache for this chapter so updates are instant
    if let Ok(mut conn) = state.redis_client.get_async_connection().await {
        let cache_key = format!("manco:chapter:pages:{}", chapter_id);
        let _: Result<(), _> = conn.del(&cache_key).await;
    }

    Ok(Json(ApiResponse {
        success: true,
        message: "Pages synced successfully to NoSQL!".to_string(),
        data: Some(chapter_id),
    }))
}

/// 5. GET /api/genres - Fetch list of genres
pub async fn get_genres(
    State(state): State<Arc<AppState>>,
) -> Result<Json<ApiResponse<Vec<Genre>>>, (StatusCode, Json<ApiResponse<String>>)> {
    let genres = sqlx::query_as::<_, Genre>(
        "SELECT id, name, slug FROM genres ORDER BY name ASC"
    )
    .fetch_all(&state.pg_pool)
    .await
    .map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ApiResponse {
                success: false,
                message: format!("Failed to load genres: {}", e),
                data: None,
            }),
        )
    })?;

    Ok(Json(ApiResponse {
        success: true,
        message: "Genres list fetched successfully".to_string(),
        data: Some(genres),
    }))
}

/// 6. POST /api/mangas - Create/Sync Manga Metadata from Laravel
pub async fn sync_manga(
    State(state): State<Arc<AppState>>,
    Json(payload): Json<CreateMangaPayload>,
) -> Result<Json<ApiResponse<Manga>>, (StatusCode, Json<ApiResponse<String>>)> {
    let slug = payload.title.to_lowercase()
        .replace(" ", "-")
        .replace(|c: char| !c.is_alphanumeric() && c != '-', "");
    
    let uuid = Uuid::new_v4();

    // Insert Manga metadata into Postgres
    let manga = sqlx::query_as::<_, Manga>(
        "INSERT INTO mangas (id, title, slug, description, cover_image, status, type, author, artist, release_year, rating, views_count, created_at, updated_at)
         VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, 0, NOW(), NOW())
         ON CONFLICT (slug) DO UPDATE 
         SET title = EXCLUDED.title, description = EXCLUDED.description, cover_image = EXCLUDED.cover_image,
             status = EXCLUDED.status, type = EXCLUDED.type, author = EXCLUDED.author, artist = EXCLUDED.artist,
             release_year = EXCLUDED.release_year, rating = EXCLUDED.rating, updated_at = NOW()
         RETURNING id, title, slug, description, cover_image, status, type, author, artist, release_year, rating::DOUBLE PRECISION as rating, views_count, created_at, updated_at"
    )
    .bind(uuid)
    .bind(&payload.title)
    .bind(&slug)
    .bind(&payload.description)
    .bind(&payload.cover_image)
    .bind(&payload.status)
    .bind(&payload.r#type)
    .bind(&payload.author)
    .bind(&payload.artist)
    .bind(payload.release_year)
    .bind(payload.rating)
    .fetch_one(&state.pg_pool)
    .await
    .map_err(|e| {
        (
            StatusCode::INTERNAL_SERVER_ERROR,
            Json(ApiResponse {
                success: false,
                message: format!("SQL Error: {}", e),
                data: None,
            }),
        )
    })?;

    // Connect genres
    for genre_slug in payload.genres {
        // Find genre ID
        let genre_opt = sqlx::query!("SELECT id FROM genres WHERE slug = $1", genre_slug)
            .fetch_optional(&state.pg_pool)
            .await
            .ok()
            .flatten();
        
        if let Some(genre_rec) = genre_opt {
            let _ = sqlx::query!(
                "INSERT INTO manga_genre (manga_id, genre_id) VALUES ($1, $2) ON CONFLICT DO NOTHING",
                manga.id,
                genre_rec.id
            )
            .execute(&state.pg_pool)
            .await;
        }
    }

    // Invalidate Redis homepage caches
    if let Ok(mut conn) = state.redis_client.get_async_connection().await {
        let _: Result<(), _> = conn.del("manco:manga:list:latest").await;
        let _: Result<(), _> = conn.del("manco:manga:list:popular").await;
        let _: Result<(), _> = conn.del("manco:manga:list:rating").await;
        let _: Result<(), _> = conn.del(format!("manco:manga:detail:{}", manga.slug)).await;
    }

    Ok(Json(ApiResponse {
        success: true,
        message: "Manga synced successfully to Postgres".to_string(),
        data: Some(manga),
    }))
}
