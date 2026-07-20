FROM rust:latest

# Install system dependencies (build-essential, ssl, pkg-config, etc.)
RUN apt-get update && apt-get install -y \
    build-essential \
    pkg-config \
    libssl-dev \
    git \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Expose the API port
EXPOSE 8000

# Start the Rust REST API server directly
CMD ["cargo", "run"]
