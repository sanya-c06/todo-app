document.addEventListener('DOMContentLoaded', () => {
    const addTaskForm = document.getElementById('addTaskForm');
    const taskInput = document.getElementById('taskInput');
    const taskList = document.getElementById('taskList');

    // --- Helper function to create a new task list item (DOM element) ---
    const createListItem = (task) => {
        const listItem = document.createElement('li');
        listItem.className = `task-item ${task.is_completed ? 'completed' : ''}`;
        listItem.setAttribute('data-task-id', task.id);

        listItem.innerHTML = `
            <input type="checkbox" 
                   class="task-complete-toggle" 
                   ${task.is_completed ? 'checked' : ''}
                   data-task-id="${task.id}">
            
            <span class="task-text">${task.description}</span>
            
            <button class="delete-btn" data-task-id="${task.id}">Delete</button>
        `;
        return listItem;
    };


    // ===================================
    // 1. ADD TASK (CREATE)
    // ===================================
    if (addTaskForm) {
        addTaskForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // STOP the default form refresh

            const taskDescription = taskInput.value.trim();
            if (!taskDescription) return;

            try {
                const response = await fetch('actions/add_task.php', {
                    method: 'POST',
                    // Data must be sent as FormData for PHP to read $_POST
                    body: new URLSearchParams({ task_description: taskDescription })
                });

                const data = await response.json();

                if (data.success) {
                    // Remove the "no tasks" message if it exists
                    const emptyMessage = taskList.querySelector('.empty-list');
                    if (emptyMessage) emptyMessage.remove();

                    // Create the new list item element
                    const newListItem = createListItem(data.task);
                    taskList.prepend(newListItem); // Add to the top of the list

                    taskInput.value = ''; // Clear the input field

                } else {
                    alert('Error adding task: ' + data.message);
                }
            } catch (error) {
                console.error('Network Error:', error);
                alert('A network error occurred.');
            }
        });
    }

    // ===================================
    // 2. DELETE TASK (DELETE)
    // ===================================
    // Use event delegation on the parent list for dynamic elements
    if (taskList) {
        taskList.addEventListener('click', async (e) => {
            // Check if the clicked element is a Delete button
            if (e.target.classList.contains('delete-btn')) {
                const button = e.target;
                const taskId = button.getAttribute('data-task-id');
                const listItem = button.closest('.task-item');

                if (!confirm('Are you sure you want to delete this task?')) return;

                try {
                    const response = await fetch('actions/delete_task.php', {
                        method: 'POST',
                        body: new URLSearchParams({ task_id: taskId })
                    });

                    const data = await response.json();

                    if (data.success) {
                        listItem.remove(); // Remove the item from the DOM
                        alert('Task deleted successfully!');

                        // Check if the list is now empty and display message
                        if (taskList.children.length === 0) {
                            taskList.innerHTML = '<li class="empty-list">You have no tasks yet!</li>';
                        }
                    } else {
                        alert('Error deleting task: ' + data.message);
                    }
                } catch (error) {
                    console.error('Network Error:', error);
                    alert('A network error occurred.');
                }
            }

            // ===================================
            // 3. UPDATE TASK (Toggle Status) - U in CRUD
            // ===================================
            if (e.target.classList.contains('task-complete-toggle')) {
                const checkbox = e.target;
                const taskId = checkbox.getAttribute('data-task-id');
                const isCompleted = checkbox.checked ? 1 : 0;
                const listItem = checkbox.closest('.task-item');

                try {
                    const response = await fetch('actions/update_task.php', {
                        method: 'POST',
                        body: new URLSearchParams({
                            task_id: taskId,
                            is_completed: isCompleted
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Success: Update the DOM class based on the new status
                        if (isCompleted) {
                            listItem.classList.add('completed');
                        } else {
                            listItem.classList.remove('completed');
                        }
                    } else {
                        // Failure: Revert the checkbox state and alert the user
                        checkbox.checked = !isCompleted;
                        alert('Error updating task status: ' + data.message);
                    }
                } catch (error) {
                    console.error('Network Error:', error);
                    checkbox.checked = !isCompleted; // Revert checkbox on network failure
                    alert('A network error occurred while updating the task.');
                }
            }
            // ===================================
            // 4. UPDATE TASK TEXT (In-Place Editing) - U in CRUD
            // ===================================
            if (e.target.classList.contains('task-text')) {
                const taskSpan = e.target;
                const listItem = taskSpan.closest('.task-item');
                const taskId = listItem.getAttribute('data-task-id');

                // If not already in edit mode
                if (!listItem.classList.contains('editing')) {
                    const currentText = taskSpan.textContent;

                    // a. Switch to Input field
                    const editInput = document.createElement('input');
                    editInput.type = 'text';
                    editInput.className = 'edit-task-input';
                    editInput.value = currentText;

                    listItem.classList.add('editing'); // Set editing status
                    taskSpan.style.display = 'none'; // Hide the span
                    taskSpan.parentNode.insertBefore(editInput, taskSpan.nextSibling); // Insert input after span

                    editInput.focus();

                    // b. Handle Save on Enter or Blur
                    const saveChanges = async () => {
                        const newText = editInput.value.trim();

                        // If text is empty or unchanged, just revert
                        if (!newText || newText === currentText) {
                            revertEdit();
                            return;
                        }

                        // AJAX call to update_task.php
                        try {
                            const response = await fetch('actions/update_task.php', {
                                method: 'POST',
                                body: new URLSearchParams({
                                    task_id: taskId,
                                    new_description: newText // Pass new text parameter
                                })
                            });

                            const data = await response.json();

                            if (data.success) {
                                // Success: Update the Span text and revert
                                taskSpan.textContent = newText;
                                revertEdit();
                            } else {
                                // Failure: Alert and revert
                                alert('Failed to save task: ' + data.message);
                                revertEdit();
                            }
                        } catch (error) {
                            console.error('Network Error:', error);
                            alert('A network error occurred while saving.');
                            revertEdit();
                        }
                    };

                    const revertEdit = () => {
                        editInput.remove();
                        taskSpan.style.display = 'inline';
                        listItem.classList.remove('editing');
                    }

                    // Listen for Enter Keypress (ASCII 13)
                    editInput.addEventListener('keypress', (event) => {
                        if (event.key === 'Enter') {
                            saveChanges();
                        }
                    });

                    // Listen for click outside/blur
                    editInput.addEventListener('blur', saveChanges);
                }
            }
        });
    }
});