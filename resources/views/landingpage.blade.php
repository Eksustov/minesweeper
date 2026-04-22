@section('title', 'Welcome')
<x-app-layout>
    <div class="py-12 flex flex-col items-center space-y-8">
        <div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-2xl">
            <h3 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Welcome to Minesweeper Live</h3>
            <p> Minesweeper Live is a game of minesweeper but a group of people try to solve it 
                together to clear the board without any mistakes. </p>
            <img src="/minesweeper-1.gif"/>
            <a href="/">
                <button class="w-full bg-white text-indigo-600 font-semibold py-2 rounded-lg hover:bg-gray-100 transition-colors"> Go to the main page </button>
            </form>
        </div>
    </div>
</x-app-layout>