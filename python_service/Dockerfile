# Use an official Python runtime as a parent image
FROM python:3.10-slim

# Set the working directory in the container
WORKDIR /app

# Install system dependencies
# tesseract-ocr is required for OCR capabilities
# build-essential might be needed for some python packages
RUN apt-get update && apt-get install -y \
    tesseract-ocr \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# Copy the requirements file into the container at /app
COPY requirements.txt .

# Install any needed packages specified in requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

# Copy the current directory contents into the container at /app
COPY . .

# Make port 8002 available to the world outside this container
EXPOSE 8002

# Define environment variable
ENV PYTHONUNBUFFERED=1

# Run uvicorn when the container launches
CMD ["uvicorn", "api:app", "--host", "0.0.0.0", "--port", "8002"]
