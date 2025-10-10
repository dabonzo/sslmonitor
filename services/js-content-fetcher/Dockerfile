FROM node:22-bookworm

# Install dependencies for Playwright Firefox
RUN apt-get update && apt-get install -y \
    fonts-liberation \
    libasound2 \
    libatk-bridge2.0-0 \
    libatk1.0-0 \
    libatspi2.0-0 \
    libcups2 \
    libdbus-1-3 \
    libdrm2 \
    libgbm1 \
    libgtk-3-0 \
    libnspr4 \
    libnss3 \
    libwayland-client0 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxkbcommon0 \
    libxrandr2 \
    xvfb \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install npm dependencies
RUN npm ci --prefer-offline --no-audit

# Install Playwright Firefox
RUN npx playwright install firefox --with-deps

# Copy application files
COPY server.js ./

# Expose port
EXPOSE 3000

# Run the service
CMD ["node", "server.js"]
