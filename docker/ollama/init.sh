#!/bin/sh

# Start Ollama in background
ollama serve &

# Wait for Ollama to be ready
echo "Waiting for Ollama to start..."
while ! curl -s http://localhost:11434/api/tags > /dev/null; do
    sleep 1
done

echo "Ollama is ready. Pulling llama3.2 model..."

# Pull the Croatian-friendly model
ollama pull llama3.2

echo "Model ready. Ollama is fully initialized."

# Keep the container running
wait
