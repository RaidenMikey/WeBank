function toggleDropdown() {
    const dropdown = document.getElementById('dropdownMenu');
    const arrow = document.getElementById('dropdownArrow');
    
    if (dropdown.classList.contains('hidden')) {
        dropdown.classList.remove('hidden');
        if (arrow) arrow.style.transform = 'rotate(180deg)';
    } else {
        dropdown.classList.add('hidden');
        if (arrow) arrow.style.transform = 'rotate(0deg)';
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('settingsDropdown');
    const dropdownMenu = document.getElementById('dropdownMenu');
    const arrow = document.getElementById('dropdownArrow');
    
    if (dropdown && !dropdown.contains(event.target)) {
        if (dropdownMenu) dropdownMenu.classList.add('hidden');
        if (arrow) arrow.style.transform = 'rotate(0deg)';
    }
});
