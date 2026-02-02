<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{ __("You're logged in!") }}
                </div>
            </div>

            <!-- Chatify Link -->
            <div class="mt-6">
                <a href="{{ url('/chatify') }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-lg text-center text-xl transition duration-150 ease-in-out shadow-lg">
                    � Open Chatify Messenger
                </a>
            </div>

            <!-- How to Use Guide -->
            <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-bold mb-4">📖 How to Start Chatting:</h3>
                    <ol class="list-decimal list-inside space-y-3">
                        <li><strong>Create another account:</strong> Register a second user in a different browser or incognito window</li>
                        <li><strong>Open Chatify:</strong> Click the blue button above to open the messenger</li>
                        <li><strong>Search for users:</strong> Click the search icon (🔍) in Chatify and type the name of the user you want to chat with</li>
                        <li><strong>Start chatting:</strong> Click on the user from search results to open the chat and start messaging!</li>
                    </ol>
                    
                    <h3 class="text-lg font-bold mb-4 mt-6">👥 NEW: Group Chat Features:</h3>
                    <ol class="list-decimal list-inside space-y-3">
                        <li><strong>Create a Group:</strong> Click on the "Groups" tab in Chatify, then click "Create New Group"</li>
                        <li><strong>Add Members:</strong> Search and select users to add to your group (at least 1 member required)</li>
                        <li><strong>Group Messaging:</strong> Click on any group to open the group chat and send messages to all members!</li>
                        <li><strong>Manage Groups:</strong> Group admins can add/remove members and update group details</li>
                    </ol>
                    
                    <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            <strong>💡 Tip:</strong> Chatify now supports both one-on-one messaging and group chats! Switch between "Contacts" and "Groups" tabs to access different conversations.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
