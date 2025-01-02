<!-- resources/views/components/early-access-modal.blade.php -->
<div x-data="{ open: false }" @open-signup.window="open = true" class="relative z-50">
    <!-- Trigger Button -->
    <button
        @click="open = true"
        class="fixed bottom-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg hover:bg-blue-700 transition">
        Get Early Access
    </button>

    <!-- Modal -->
    <template x-if="open">
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div
                    @click="open = false"
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75">
                </div>

                <div class="relative w-full max-w-md p-6 mx-auto bg-white rounded-lg shadow-xl">
                    <div class="sm:flex sm:items-start">
                        <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900 mb-4">
                                Get Early Access to Premium Features
                            </h3>

                            <div class="mt-2 mb-4">
                                <ul class="space-y-2 text-sm text-gray-600">
                                    <li class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Historical odds analysis
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Custom alerts for value opportunities
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Advanced analytics dashboard
                                    </li>
                                </ul>
                            </div>

                            <form action="{{ route('signup.store') }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <input type="email"
                                           name="email"
                                           required
                                           class="w-full px-3 py-2 border rounded-md"
                                           placeholder="Enter your email">
                                </div>
                                <button type="submit"
                                        class="w-full px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                    Join Waitlist
                                </button>
                            </form>
                        </div>
                    </div>

                    <button
                        @click="open = false"
                        class="absolute top-2 right-2 text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
