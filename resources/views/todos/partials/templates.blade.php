
// <div class="bg-white rounded-lg shadow overflow-hidden">

//     <div class="grid grid-cols-7 text-center text-xs text-gray-700 bg-gray-100">
//         <div class="py-2 text-red-600">日</div>
//         <div class="py-2">月</div>
//         <div class="py-2">火</div>
//         <div class="py-2">水</div>
//         <div class="py-2">木</div>
//         <div class="py-2">金</div>
//         <div class="py-2 text-blue-600">土</div>
//     </div>


//     <div class="grid grid-cols-7 border-t border-l border-gray-200">
//         @php
//             $startMonth = $date->copy()->startOfMonth();
//             $endMonth = $date->copy()->endOfMonth();


//             $startDate = $startMonth->copy()->startOfWeek();

//             $endDate = $endMonth->copy()->endOfWeek();

//             $today = now()->startOfDay();
//         @endphp

//         @for ($day = $startDate; $day <= $endDate; $day->addDay())
//             @php
//                 $isCurrentMonth = $day->month === $date->month;
//                 $isToday = $day->isSameDay($today);
//                 $dayTodos = $todos->filter(function($todo) use ($day) {
//                     return $todo->due_date && $todo->due_date->isSameDay($day);
//                 });
//             @endphp

//             <div class="min-h-32 p-1 border-r border-b border-gray-200
//                         {{ $isCurrentMonth ? 'bg-white' : 'bg-gray-50' }}
//                         {{ $isToday ? 'bg-yellow-50' : '' }}">


//                 <a href="{{ route('todos.index', ['view' => 'date', 'date' => $day->format('Y-m-d')]) }}"
//                    class="block text-center py-1 {{ $isToday ? 'font-bold bg-yellow-100 rounded-full w-6 h-6 mx-auto' : '' }}
//                           {{ !$isCurrentMonth ? 'text-gray-400' : '' }}
//                           {{ $day->dayOfWeek === 0 ? 'text-red-600' : '' }}
//                           {{ $day->dayOfWeek === 6 ? 'text-blue-600' : '' }}">
//                     {{ $day->day }}
//                 </a>


//                 <div class="mt-1 text-xs space-y-1 max-h-28 overflow-y-auto">
//                     @foreach ($dayTodos->take(3) as $todo)
//                         <div class="flex items-center p-1 rounded hover:bg-gray-100
//                                     {{ $todo->status === 'completed' ? 'text-gray-400 line-through' : '' }}"
//                              style="border-left: 2px solid {{ $todo->category ? $todo->category->color : '#ddd' }}">
//                             <form action="{{ route('todos.toggle', $todo) }}" method="POST" class="mr-1 flex-shrink-0">
//                                 @csrf
//                                 @method('PATCH')
//                                 <input type="checkbox" onChange="this.form.submit()"
//                                        {{ $todo->status === 'completed' ? 'checked' : '' }}
//                                        class="w-3 h-3">
//                             </form>
//                             <span class="truncate cursor-pointer" onclick="editTodo({{ $todo->id }}, {{ json_encode([
//                                 'title' => $todo->title,
//                                 'due_date' => $todo->due_date ? $todo->due_date->format('Y-m-d') : null,
//                                 'due_time' => $todo->due_time ? $todo->due_time->format('H:i') : null,
//                                 'category_id' => $todo->category_id,
//                                 'recurrence_type' => $todo->recurrence_type,
//                                 'recurrence_end_date' => $todo->recurrence_end_date ? $todo->recurrence_end_date->format('Y-m-d') : null,
//                             ]) }})">
//                                 {{ $todo->title }}
//                                 @if ($todo->due_time)
//                                     <span class="text-gray-500">{{ $todo->due_time->format('H:i') }}</span>
//                                 @endif
//                             </span>
//                         </div>
//                     @endforeach

//                     @if ($dayTodos->count() > 3)
//                         <div class="text-xs text-blue-500 text-center">
//                             <a href="{{ route('todos.index', ['view' => 'date', 'date' => $day->format('Y-m-d')]) }}">
//                                 + {{ $dayTodos->count() - 3 }} more
//                             </a>
//                         </div>
//                     @endif
//                 </div>
//             </div>
//         @endfor
//     </div>
// </div>
