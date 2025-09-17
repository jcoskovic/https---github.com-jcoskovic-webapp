FROM python:3.9-slim

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    gcc \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements and install Python dependencies from ml-service directory
COPY ml-service/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy source code from ml-service directory
COPY ml-service/ .

EXPOSE 5000

CMD ["python", "app.py"]
