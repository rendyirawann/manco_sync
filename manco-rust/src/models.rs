use serde::{Deserialize, Serialize};
use uuid::Uuid;
use chrono::{DateTime, Utc};

// --- Relational SQL Models (PostgreSQL) ---

#[derive(Debug, Clone, Serialize, Deserialize, sqlx::FromRow)]
pub struct Manga {
    pub id: Uuid,
    pub title: String,
    pub slug: String,
    pub description: Option<String>,
    pub cover_image: Option<String>,
    pub status: String,
    pub r#type: String,
    pub author: Option<String>,
    pub artist: Option<String>,
    pub release_year: Option<i32>,
    pub rating: Option<f64>, // Selected as DOUBLE PRECISION in SQL queries
    pub views_count: i64,
    pub created_at: Option<DateTime<Utc>>,
    pub updated_at: Option<DateTime<Utc>>,
}

#[derive(Debug, Clone, Serialize, Deserialize, sqlx::FromRow)]
pub struct Chapter {
    pub id: Uuid,
    pub manga_id: Uuid,
    pub chapter_number: f64, // Stored as Numeric/Decimal but mapped to f64
    pub title: String,
    pub slug: String,
    pub views_count: i64,
    pub created_at: Option<DateTime<Utc>>,
    pub updated_at: Option<DateTime<Utc>>,
}

#[derive(Debug, Clone, Serialize, Deserialize, sqlx::FromRow)]
pub struct Genre {
    pub id: Uuid,
    pub name: String,
    pub slug: String,
}

// --- NoSQL Models (MongoDB Document Store) ---

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct PageDetail {
    pub page_number: i32,
    pub image_url: String,
    pub width: Option<i32>,
    pub height: Option<i32>,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct ChapterPagesDoc {
    pub chapter_id: String,
    pub pages: Vec<PageDetail>,
    pub synced_at: DateTime<Utc>,
}

// --- Request Payloads ---

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct CreateMangaPayload {
    pub title: String,
    pub description: Option<String>,
    pub cover_image: Option<String>,
    pub status: String,
    pub r#type: String,
    pub author: Option<String>,
    pub artist: Option<String>,
    pub release_year: Option<i32>,
    pub rating: Option<f64>,
    pub genres: Vec<String>, // Genre names or slugs to associate
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct CreateChapterPayload {
    pub manga_id: Uuid,
    pub chapter_number: f64,
    pub title: String,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SyncChapterPagesPayload {
    pub pages: Vec<String>, // Array of image URLs/paths
}

// --- Generic API Response ---

#[derive(Debug, Serialize)]
pub struct ApiResponse<T> {
    pub success: bool,
    pub message: String,
    pub data: Option<T>,
}
