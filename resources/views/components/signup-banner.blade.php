<div x-data="{ show: true }" x-show="show" class="relative bg-blue-600">
    <div class="max-w-7xl mx-auto py-3 px-3 sm:px-6 lg:px-8">
        <div class="pr-16 sm:text-center sm:px-16">
            <p class="font-medium text-white">
                <span class="md:inline">Get early access to new features!</span>
                <span class="block sm:ml-2 sm:inline-block">
                    <button @click="$dispatch('open-signup')" class="text-white font-semibold underline">Get first access to new features</button>
                </span>
            </p>
        </div>
        <div class="absolute inset-y-0 right-0 pt-1 pr-1 flex items-start sm:pt-1 sm:pr-2 sm:items-start">
            <button
                @click="show = false"
                type="button"
                class="flex p-2">
                <span class="sr-only">Dismiss</span>
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</div>
