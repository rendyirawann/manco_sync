use std::env;

#[derive(Clone, Debug)]
pub struct Config {
    pub database_url: String,
    pub mongodb_uri: String,
    pub redis_url: String,
    pub port: u16,
}

impl Config {
    pub fn from_env() -> Self {
        // Read .env if present
        dotenvy::dotenv().ok();

        let database_url = env::var("DATABASE_URL")
            .unwrap_or_else(|_| "postgres://postgres:in12345@127.0.0.1:5433/manco_sync".to_string());
        
        let mongodb_uri = env::var("MONGODB_URI")
            .unwrap_or_else(|_| "mongodb://127.0.0.1:27017".to_string());

        let redis_url = env::var("REDIS_URL")
            .unwrap_or_else(|_| "redis://127.0.0.1:6379".to_string());

        let port = env::var("PORT")
            .unwrap_or_else(|_| "8000".to_string())
            .parse::<u16>()
            .unwrap_or(8000);

        Self {
            database_url,
            mongodb_uri,
            redis_url,
            port,
        }
    }
}
