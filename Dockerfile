# ==========================================
# Stage 1: Builder
# ==========================================
FROM php:8-apache AS builder

ENV PYTHONUNBUFFERED=1 \
    UV_LINK_MODE=copy

# Install dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev unzip python3 python3-venv python3-dev build-essential curl \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Install uv
RUN curl -LsSf https://astral.sh/uv/install.sh | UV_INSTALL_DIR=/usr/local/bin sh

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Install Python dependencies
COPY model/pyproject.toml model/uv.lock ./model/
WORKDIR /var/www/html/model
RUN uv sync --frozen --no-dev

# Clean up Python cache to save space
RUN rm -rf /root/.cache/uv

# Copy Application Code
WORKDIR /var/www/html
COPY . .

# Set permissions for uploads directory
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# CRITICAL: Set execute permissions on Python virtual environment
RUN chmod -R 755 /var/www/html/model/.venv/bin

# ==========================================
# Stage 2: Production
# ==========================================
FROM php:8-apache

ENV PYTHONUNBUFFERED=1 \
    PATH="/var/www/html/model/.venv/bin:${PATH}" \
    MPLBACKEND=Agg \
    QT_LOGGING_RULES="*.debug=false;qt.qpa.*=false"

# Install runtime libs
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq5 python3 python3-venv libglib2.0-0 libgl1 \
    fonts-dejavu-core fonts-liberation \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

# Copy PHP configs
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Copy the application from Builder
COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html

# CRITICAL: Ensure execute permissions are set on Python binaries AFTER copying
RUN chmod -R 755 /var/www/html/model/.venv/bin

# Create directories with proper permissions
RUN mkdir -p /var/www/html/uploads /var/www/html/log \
    && chown -R www-data:www-data /var/www/html/uploads /var/www/html/log \
    && chmod -R 775 /var/www/html/uploads /var/www/html/log

# Verify Python is executable (this will fail build if it's not)
RUN /var/www/html/model/.venv/bin/python3 --version

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD python3 -c "import urllib.request; urllib.request.urlopen('http://localhost')" || exit 1

EXPOSE 80
CMD [ "apache2-foreground" ]
