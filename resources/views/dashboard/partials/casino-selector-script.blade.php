{{-- resources/views/dashboard/partials/casino-selector-script.blade.php --}}
<script>
    // First, expose these functions to the window object so they can be called from HTML
    window.toggleCasinoSelector = function() {
        const form = document.getElementById('casino-selector-form');
        const arrow = document.getElementById('selector-arrow');
        form.classList.toggle('hidden');
        arrow.classList.toggle('rotate-180');
    };

    window.closeExplanation = function() {
        const header = document.getElementById('explanation-header');
        header.style.display = 'none';
        localStorage.setItem('explanationClosed', 'true');
    };

    // Initialize casino selection functionality
    function initializeCasinoSelector() {
        const buttons = document.querySelectorAll('.casino-btn');
        const hiddenInput = document.getElementById('selected-casinos');
        const maxSelections = 4;

        if (!buttons.length || !hiddenInput) {
            console.warn('Casino selector elements not found');
            return;
        }

        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const casino = this.dataset.casino;
                let selected = hiddenInput.value ? hiddenInput.value.split(',').filter(Boolean) : [];

                if (this.classList.contains('bg-blue-100')) {
                    // Deselect
                    selected = selected.filter(item => item !== casino);
                    this.classList.remove('bg-blue-100', 'border-blue-500');
                    this.classList.add('bg-white', 'border-gray-300');
                } else if (selected.length < maxSelections) {
                    // Select
                    selected.push(casino);
                    this.classList.remove('bg-white', 'border-gray-300');
                    this.classList.add('bg-blue-100', 'border-blue-500');
                }

                hiddenInput.value = selected.join(',');
            });
        });
    }

    // Handle explanation header visibility
    function initializeExplanationHeader() {
        if (localStorage.getItem('explanationClosed') === 'true') {
            const header = document.getElementById('explanation-header');
            if (header) {
                header.style.display = 'none';
            }
        }
    }

    // Initialize everything when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        initializeCasinoSelector();
        initializeExplanationHeader();
    });
</script>
