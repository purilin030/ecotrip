<?php 
$flash = ''; 
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); } 
?> 

<div class="w-full">
  <?php if ($flash !== '') { ?> 
    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded" role="alert">
        <?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?>
    </div> 
  <?php } ?> 
 
  <form method="post" action="submission_save.php" enctype="multipart/form-data"> 
    
    <div class="mb-6"> 
      <label class="block text-sm font-medium text-gray-700 mb-2">Caption</label> 
      <input class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500" 
             name="caption" 
             maxlength="255" 
             placeholder="Optional: max length: 255 words"> 
    </div> 
 
    <div class="mb-6"> 
      <label class="block text-sm font-medium text-gray-700 mb-2">Upload New Photo (JPG/PNG, Max 2MB)</label>
      
      <div class="flex items-center gap-3">
          <input type="file" name="photo" id="photo-upload" class="hidden" accept="image/jpeg,image/png" required
                 onchange="updateFileName(this)">

          <label for="photo-upload" class="cursor-pointer bg-green-50 text-green-700 font-semibold py-2 px-4 rounded border border-green-100 hover:bg-green-100 transition shadow-sm">
              Choose File
          </label>

          <span id="file-chosen-text" class="text-sm text-gray-500 italic">
              Recommend picture resolution: 320x320
          </span>
      </div>
    </div>
    <div class="flex items-center gap-4">
        <button class="w-full bg-brand-600 text-white font-bold py-2 px-6 rounded hover:bg-brand-700 transition duration-300">
            Update Profile
        </button> 
    </div>
  </form> 
</div>

<script>

  // upload picture function
function updateFileName(input) {
    const fileNameDisplay = document.getElementById('file-chosen-text');
    if (input.files && input.files.length > 0) {
        // If a file is selected, display the file name
        fileNameDisplay.textContent = input.files[0].name;
        fileNameDisplay.classList.remove('italic', 'text-gray-500'); // Optional: remove italic and darken color
        fileNameDisplay.classList.add('text-gray-800');
    } else {
        // If none selected (canceled), restore default prompt
        fileNameDisplay.textContent = "Recommend picture resolution: 320x320";
        fileNameDisplay.classList.add('italic', 'text-gray-500');
    }
}
</script>