# Use Node.js 20
FROM node:20-slim

# Set working directory
WORKDIR /app

# Copy package.json and npm config
COPY package.json .npmrc .nvmrc ./
COPY frontend/package.json frontend/.npmrc frontend/.nvmrc ./frontend/

# Remove any existing package-lock.json files
RUN rm -f package-lock.json frontend/package-lock.json

# Install dependencies with legacy peer deps (directly, not via npm script)
RUN npm install --legacy-peer-deps --no-package-lock

# Install frontend dependencies
RUN cd frontend && npm install --legacy-peer-deps

# Copy source code
COPY . .

# Build the application
RUN npm run build

# Expose port
EXPOSE 4200

# Start the application
CMD ["npm", "start"]