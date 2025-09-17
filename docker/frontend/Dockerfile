FROM node:20-alpine

WORKDIR /app

# Copy package files from frontend directory
COPY frontend/package*.json ./

# Install dependencies
RUN npm install

# Copy source code from frontend directory
COPY frontend/ .

# Expose port
EXPOSE 4200

# Start development server
CMD ["npm", "start"]
