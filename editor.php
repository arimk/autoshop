<?php
session_start();
require_once 'config.php';

// Check authentication
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: index.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoShop - Image Editor</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Header Bar -->
    <div class="bg-white shadow-md w-full">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">AutoShop Image Editor</h1>
                <a href="?logout" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition-colors">Logout</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex min-h-0">
        <!-- Main Content Area (75%) -->
        <div id="mainContent" class="w-3/4 p-8 overflow-hidden flex">
            <div class="bg-white rounded-xl shadow-lg p-8 flex flex-col w-full max-w-[1400px] mx-auto">
                <div class="mb-6">
                    <textarea id="prompt" class="w-full h-32 p-4 border rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg" 
                        placeholder="Enter your prompt here..."></textarea>
                </div>
                
                <div id="imageContainer" class="flex-1 mb-6 border-2 border-dashed border-gray-300 rounded-lg p-4 overflow-hidden relative" style="max-height: calc(100vh - 340px);">
                    <div id="dropZone" class="h-full flex items-center justify-center relative">
                        <div id="uploadPrompt" class="absolute inset-0 flex flex-col items-center justify-center">
                            <p class="mb-4 text-gray-600 text-lg">Drag and drop an image here or</p>
                            <button id="uploadBtn" class="bg-blue-500 text-white px-8 py-3 rounded-lg hover:bg-blue-600 transition-colors text-lg">
                                Choose File
                            </button>
                            <input type="file" id="fileInput" class="hidden" accept="image/*">
                        </div>
                        <img id="previewImage" class="max-w-full max-h-full object-contain hidden" src="" alt="Preview">
                    </div>
                    <button id="removeImageBtn" class="absolute top-4 right-4 bg-red-500 text-white p-2 rounded-full hidden hover:bg-red-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <button id="generateBtn" class="w-full bg-green-500 text-white py-4 px-6 rounded-lg hover:bg-green-600 transition-colors text-lg font-semibold">
                    Generate Image
                </button>
            </div>
        </div>

        <!-- History Panel (25%) -->
        <div id="historyPanel" class="w-1/4 bg-gray-50 p-8 border-l border-gray-200 overflow-hidden">
            <div class="bg-white rounded-xl shadow-lg p-6 h-full flex flex-col">
                <div class="flex justify-between items-center mb-4 pb-4 border-b">
                    <h2 class="text-xl font-bold text-gray-800">History</h2>
                    <button id="clearHistoryBtn" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition-colors text-sm">
                        Clear History
                    </button>
                </div>
                <div id="historyContainer" class="flex-1 space-y-4 overflow-y-auto pr-2">
                    <!-- History items will be added here dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white p-8 rounded-xl shadow-2xl">
            <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-500 border-t-transparent"></div>
            <p class="mt-4 text-center text-lg font-medium">Generating image...</p>
        </div>
    </div>

    <script>
        // Initialize the first system prompt
        const systemPrompt = {
            role: "user",
            parts: [{
                text: "You are helping a user to create or edit an image. You will receive each time instruction for your next step to edit or create. Only answer with an image. Try to be the closest to the user needs. If editing an image, try to keep the style of the image as close as possible"
            }]
        };

        // Initialize history with system prompt
        let history = [systemPrompt];
        let activeHistoryIndex = -1; // Track which history item is active
        let currentImage = null;

        // File Upload Handling
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const uploadBtn = document.getElementById('uploadBtn');
        const previewImage = document.getElementById('previewImage');
        const uploadPrompt = document.getElementById('uploadPrompt');
        const removeImageBtn = document.getElementById('removeImageBtn');

        uploadBtn.addEventListener('click', () => fileInput.click());
        removeImageBtn.addEventListener('click', removeCurrentImage);

        function removeCurrentImage() {
            currentImage = null;
            previewImage.src = '';
            previewImage.classList.add('hidden');
            uploadPrompt.classList.remove('hidden');
            removeImageBtn.classList.add('hidden');
        }

        fileInput.addEventListener('change', handleFileSelect);
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-blue-500');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        });

        function handleFileSelect(e) {
            const file = e.target.files[0];
            if (file) {
                handleFile(file);
            }
        }

        function handleFile(file) {
            if (!file.type.startsWith('image/')) {
                alert('Please upload an image file');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                currentImage = e.target.result;
                previewImage.src = currentImage;
                previewImage.classList.remove('hidden');
                uploadPrompt.classList.add('hidden');
                removeImageBtn.classList.remove('hidden');
                
                // Ensure the image container adjusts to the image
                const dropZone = document.getElementById('dropZone');
                if (previewImage.naturalHeight > previewImage.naturalWidth) {
                    dropZone.style.minHeight = '500px';
                }
            };
            reader.readAsDataURL(file);
        }

        // Generate Image
        document.getElementById('generateBtn').addEventListener('click', async () => {
            const prompt = document.getElementById('prompt').value.trim();
            if (!prompt) {
                alert('Please enter a prompt');
                return;
            }

            document.getElementById('loadingOverlay').classList.remove('hidden');

            // If we have an active history item, trim history to that point before adding new content
            if (activeHistoryIndex !== -1) {
                history = history.slice(0, activeHistoryIndex + 1);
            }

            // Add the current prompt and image (if any) to the request
            const currentPrompt = {
                role: "user",
                parts: [{ text: prompt }]
            };

            if (currentImage) {
                currentPrompt.parts.push({
                    inlineData: {
                        mimeType: "image/jpeg",
                        data: currentImage.split(',')[1]
                    }
                });
            }

            history.push(currentPrompt);

            try {
                const response = await fetch('./api/generate_image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        contents: history
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                
                if (data.candidates && data.candidates[0].finishReason === 'IMAGE_SAFETY') {
                    throw new Error('The image could not be generated due to safety concerns. Please try a different prompt or image.');
                }
                
                if (!data.candidates || !data.candidates[0].content || !data.candidates[0].content.parts) {
                    throw new Error('Unexpected API response format. Please try again.');
                }

                const generatedImage = data.candidates[0].content.parts[0].inlineData.data;
                
                // Update the preview with the generated image
                currentImage = `data:image/png;base64,${generatedImage}`;
                previewImage.src = currentImage;
                previewImage.classList.remove('hidden');
                uploadPrompt.classList.add('hidden');
                removeImageBtn.classList.remove('hidden');

                // Add the response to history
                history.push({
                    role: "model",
                    parts: [{
                        inlineData: {
                            mimeType: "image/png",
                            data: generatedImage
                        }
                    }]
                });

                activeHistoryIndex = -1; // Reset active index for new generation
                updateHistoryDisplay();

                // Clear the prompt
                document.getElementById('prompt').value = '';
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while generating the image');
            } finally {
                document.getElementById('loadingOverlay').classList.add('hidden');
            }
        });

        function updateHistoryDisplay() {
            const container = document.getElementById('historyContainer');
            container.innerHTML = ''; // Clear and rebuild entire history

            // Create history items in reverse order (newest first)
            for (let i = history.length - 1; i > 0; i -= 2) { // Start from last item, skip system prompt
                if (history[i].role === 'model' && history[i-1].role === 'user') {
                    const historyItem = document.createElement('div');
                    const isActive = i === activeHistoryIndex || (activeHistoryIndex === -1 && i === history.length - 1);
                    
                    historyItem.className = `border rounded-lg p-4 mb-4 cursor-pointer transition-all ${
                        isActive ? 'bg-white shadow-md ring-2 ring-blue-500' : 'bg-white shadow-sm hover:shadow-md'
                    }`;
                    historyItem.dataset.index = i;

                    // Add prompt text
                    const promptDiv = document.createElement('div');
                    promptDiv.className = 'text-sm text-gray-600 mb-2 line-clamp-2';
                    promptDiv.textContent = history[i-1].parts[0].text;
                    historyItem.appendChild(promptDiv);

                    // Add image
                    const img = document.createElement('img');
                    img.src = `data:image/png;base64,${history[i].parts[0].inlineData.data}`;
                    img.className = 'w-full h-auto rounded-md';
                    historyItem.appendChild(img);

                    // Add click handler
                    historyItem.addEventListener('click', () => selectHistoryItem(i));

                    container.appendChild(historyItem);
                }
            }

            // Scroll to top after adding new item
            container.scrollTop = 0;
        }

        function selectHistoryItem(index) {
            activeHistoryIndex = index;
            
            // Update the current image preview
            const selectedImage = history[index].parts[0].inlineData.data;
            currentImage = `data:image/png;base64,${selectedImage}`;
            previewImage.src = currentImage;
            previewImage.classList.remove('hidden');
            uploadPrompt.classList.add('hidden');
            removeImageBtn.classList.remove('hidden');
            
            // Update history display to highlight selected item
            updateHistoryDisplay();
        }

        function clearHistory() {
            const container = document.getElementById('historyContainer');
            container.innerHTML = '';
            history = [systemPrompt]; // Keep only the system prompt
            activeHistoryIndex = -1;
            
            // Clear current image
            currentImage = null;
            previewImage.src = '';
            previewImage.classList.add('hidden');
            uploadPrompt.classList.remove('hidden');
            removeImageBtn.classList.add('hidden');
        }

        // Initialize clear history button
        document.getElementById('clearHistoryBtn').addEventListener('click', clearHistory);
    </script>
</body>
</html> 