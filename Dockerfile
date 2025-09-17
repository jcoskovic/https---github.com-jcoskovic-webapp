# Use Node.js 22
FROM node:22-slim

# Set working directory
WORKDIR /app

# Copy package.json and npm config
COPY package.json .npmrc .nvmrc ./
COPY frontend/package.json frontend/.npmrc frontend/.nvmrc ./frontend/

# Remove any existing package-lock.json files  
RUN rm -f package-lock.json frontend/package-lock.json

# Clear npm cache and install dependencies with legacy peer deps
RUN npm cache clean --force
RUN npm install --legacy-peer-deps --no-package-lock

# Install frontend dependencies with fresh cache
RUN cd frontend && npm cache clean --force
RUN cd frontend && npm install --legacy-peer-deps

# Copy source code
COPY . .

# Build the application
RUN npm run build

# Expose port
EXPOSE 4200

# Start the application
CMD ["npm", "start"]