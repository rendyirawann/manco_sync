use axum::{
    routing::{get, post},
    Router,
};
use std::net::SocketAddr;
use std::sync::Arc;
use tower_http::cors::{Any, CorsLayer};
use tower_http::trace::TraceLayer;
use tracing_subscriber::{layer::SubscriberExt, util::SubscriberInitExt};

mod config;
mod models;
mod handlers;

use crate::config::Config;
use crate::handlers::{
    AppState, list_mangas, get_manga_detail, get_chapter_pages, sync_chapter_pages, get_genres, sync_manga
};

#[tokio::main]
async fn main() {
    // 1. Initialize Logging / Tracing
    tracing_subscriber::registry()
        .with(
            tracing_subscriber::EnvFilter::try_from_default_env()
                .unwrap_or_else(|_| "manco_rust=debug,tower_http=debug".into()),
        )
        .with(tracing_subscriber::fmt::layer())
        .init();

    // 2. Load Environment Config
    let config = Config::from_env();
    tracing::info!("Starting Manco Rust API backend...");
    tracing::info!("Postgres DB: {}", config.database_url);
    tracing::info!("MongoDB URI: {}", config.mongodb_uri);
    tracing::info!("Redis URL: {}", config.redis_url);

    // 3. Setup PostgreSQL Connection Pool
    let pg_pool = sqlx::postgres::PgPoolOptions::new()
        .max_connections(50)
        .acquire_timeout(std::time::Duration::from_secs(5))
        .connect(&config.database_url)
        .await
        .unwrap_or_else(|e| {
            tracing::error!("CRITICAL: Failed to connect to PostgreSQL: {}", e);
            // Don't panic in Docker environment; retry or allow container to start
            panic!("PostgreSQL connection failed: {}", e);
        });
    tracing::info!("Successfully connected to PostgreSQL!");

    // 4. Setup MongoDB Client
    let mut mongo_options = mongodb::options::ClientOptions::parse(&config.mongodb_uri)
        .await
        .expect("Failed to parse MongoDB URI");
    mongo_options.app_name = Some("MancoSyncRust".to_string());
    let mongo_client = mongodb::Client::with_options(mongo_options)
        .expect("Failed to initialize MongoDB client");
    tracing::info!("Successfully initialized MongoDB NoSQL Client!");

    // 5. Setup Redis Cache Client
    let redis_client = redis::Client::open(config.redis_url.clone())
        .expect("Failed to connect to Redis");
    tracing::info!("Successfully initialized Redis cache client!");

    // 6. Assemble Shared AppState
    let state = Arc::new(AppState {
        pg_pool,
        mongo_client,
        redis_client,
    });

    // 7. Configure CORS (Cross-Origin Resource Sharing)
    let cors = CorsLayer::new()
        .allow_origin(Any)
        .allow_methods(Any)
        .allow_headers(Any);

    // 8. Define API Routes
    let app = Router::new()
        // Consumer Routes
        .route("/api/mangas", get(list_mangas))
        .route("/api/mangas/:slug", get(get_manga_detail))
        .route("/api/chapters/:chapter_id/pages", get(get_chapter_pages))
        .route("/api/genres", get(get_genres))
        
        // Admin Synchronization / Ingestion Routes
        .route("/api/sync/manga", post(sync_manga))
        .route("/api/chapters/:chapter_id/pages", post(sync_chapter_pages))
        
        // Middleware layers
        .layer(cors)
        .layer(TraceLayer::new_for_http())
        .with_state(state);

    // 9. Start Server
    let addr = SocketAddr::from(([0, 0, 0, 0], config.port));
    tracing::info!("Server listening on http://{}", addr);
    
    let listener = tokio::net::TcpListener::bind(&addr).await.unwrap();
    axum::serve(listener, app).await.unwrap();
}
