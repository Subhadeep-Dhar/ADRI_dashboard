<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADRI dashboard</title>
    <style>
        /* Basic styling */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        #calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            max-width: 800px;
            margin: 20px auto;
        }

        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            font-weight: bold;
        }

        .day {
            /* border: 1px solid #ddd; */
            padding: 50px;
            cursor: pointer;
            position: relative;
        }

        .day:hover {
            background-color: #f0f0f0;
        }

        .day .date {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 12px;
            color: #666;
        }

        .event {
            background-color: #4caf50;
            color: #fff;
            margin: 5px 0;
            padding: 5px;
            border-radius: 3px;
            font-size: 12px;
            cursor: pointer;
        }

        .event:hover {
            background-color: #e53935;
        }

        #eventBox {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            z-index: 1000;
            width: 300px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.3);
        }

        #eventBox input,
        #eventBox textarea,
        #eventBox button {
            display: block;
            width: 100%;
            margin: 10px 0;
        }

        #overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        #eventDetails {
            font-size: 14px;
            margin-top: 10px;
        }



        #eventDetails select {
            margin-top: 10px;
            padding: 5px;
            font-size: 14px;
            width: 100%;
        }

        #eventDetails button {
            margin: 10px 5px;
            padding: 8px 12px;
            background-color: #4caf50; /* Green for Confirm */
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
        }

        #eventDetails button#cancelRemoveEvent {
            background-color: #ff4d4d; /* Red for Cancel */
        }

        #eventDetails button:hover {
            opacity: 0.9;
        }

    </style>
</head>
<body>
    <h1>Interactive Calendar with Event Description</h1>

    <!-- Calendar Navigation -->
    <div id="calendarNavigation">
        <button id="prevMonth">&lt; Prev</button>
        <select id="monthSelector"></select>
        <select id="yearSelector"></select>
        <button id="nextMonth">Next &gt;</button>
    </div>

    <div id="calendar"></div>    

    <!-- Event Box -->
    <div id="overlay"></div>
    <div id="eventBox">
        <h3 id="eventBoxTitle">Add Event</h3>
        <div id="eventDetails" style="display: none;"></div>
        <form id="eventForm">
            <input type="text" id="eventName" placeholder="Event Name" required />
            <label for="startDate">Start Date</label>
            <input type="date" id="startDate" required />
            <label for="endDate">End Date (Optional)</label>
            <input type="date" id="endDate" />
            <label for="startTime">Start Time</label>
            <input type="time" id="startTime" required />
            <label for="endTime">End Time</label>
            <input type="time" id="endTime" required />
            <label for="eventDescription">Event Description (Optional)</label>
            <textarea id="eventDescription" rows="4" placeholder="Add an optional event description..."></textarea>
            <button type="submit">Save</button>
        </form>
        <button id="removeEventButton" style="display: none;" onclick="removeEvent()">Remove Event</button>
        <button onclick="closeBox()">Cancel</button>
    </div>

    <script>
    const calendar = document.getElementById("calendar");
    const eventBox = document.getElementById("eventBox");
    const overlay = document.getElementById("overlay");
    const eventForm = document.getElementById("eventForm");
    const eventDetails = document.getElementById("eventDetails");
    const removeEventButton = document.getElementById("removeEventButton");
    const eventBoxTitle = document.getElementById("eventBoxTitle");

    let events = {};
    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();



    // Populate month and year selectors
    function populateSelectors() {
        monthSelector.innerHTML = "";
        yearSelector.innerHTML = "";

        const months = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];

        months.forEach((month, index) => {
            const option = document.createElement("option");
            option.value = index;
            option.textContent = month;
            if (index === currentMonth) option.selected = true;
            monthSelector.appendChild(option);
        });

        const startYear = currentYear - 50;
        const endYear = currentYear + 50;
        for (let year = startYear; year <= endYear; year++) {
            const option = document.createElement("option");
            option.value = year;
            option.textContent = year;
            if (year === currentYear) option.selected = true;
            yearSelector.appendChild(option);
        }
    }

    function getRandomColor() {
        const letters = '0123456789ABCDEF';
        let color = '#';
        for (let i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }

    // Save event
eventForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const eventName = document.getElementById("eventName").value;
    const startDate = document.getElementById("startDate").value;
    const endDate = document.getElementById("endDate").value || startDate;
    const startTime = document.getElementById("startTime").value;
    const endTime = document.getElementById("endTime").value;
    const eventDescription = document.getElementById("eventDescription").value;

    let currentDate = new Date(startDate);
    const endDateObj = new Date(endDate);

    // Ensure the event is added only once for each day in the range
    while (currentDate <= endDateObj) {
        const dateKey = currentDate.toISOString().split("T")[0]; // Format as YYYY-MM-DD

        if (!events[dateKey]) {
            events[dateKey] = [];
        }

        // Add event only if it doesn't already exist on the current date
        if (!events[dateKey].some(event => event.name === eventName && event.startTime === startTime && event.endTime === endTime)) {
            events[dateKey].push({ name: eventName, startDate, endDate, startTime, endTime, description: eventDescription });
        }

        // Move to the next day
        currentDate.setDate(currentDate.getDate() + 1);
    }

    closeBox();
    generateCalendar(new Date().getFullYear(), new Date().getMonth());
});

    // Generate calendar
    function generateCalendar(year, month) {
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstDay = new Date(year, month, 1).getDay();
        calendar.innerHTML = "";

        // Add header
        const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
        daysOfWeek.forEach((day) => {
            const header = document.createElement("div");
            header.className = "calendar-header";
            header.textContent = day;
            calendar.appendChild(header);
        });

        // Add blank spaces for previous month
        for (let i = 0; i < firstDay; i++) {
            const blank = document.createElement("div");
            calendar.appendChild(blank);
        }

        // Add days
        "<center>"
        for (let day = 1; day <= daysInMonth; day++) {
            const dayCell = document.createElement("div");
            dayCell.className = "day";
            dayCell.dataset.date = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
            dayCell.innerHTML = `<span class="date">${day}</span>`;

            // Add event indicator for each event
            const dateKey = dayCell.dataset.date;
            if (events[dateKey] && events[dateKey].length > 0) {
                events[dateKey].forEach((event) => {
                    const eventElement = document.createElement("div");
                    eventElement.className = "event";
                    eventElement.textContent = event.name;
                    eventElement.style.backgroundColor = event.color; // Apply the random color
                    dayCell.appendChild(eventElement);
                });
            }

            dayCell.addEventListener("click", () => openBox(dayCell.dataset.date));
            calendar.appendChild(dayCell);
        }
        "</center>"
    }
 
    
    // Open event box
    function openBox(date) {
        currentDate = date;
        const dateKey = currentDate;

        eventBox.style.display = "block";
        overlay.style.display = "block";

        if (events[dateKey]) {
            // Show event details for existing events
            const eventList = events[dateKey]
                .map(
                    (event) =>
                        `<strong>Event:</strong> ${event.name}<br>
                        <strong>Start:</strong> ${event.startTime} (${event.startDate})<br>
                        <strong>End:</strong> ${event.endTime} (${event.endDate || "Same Day"})<br>
                        <strong>Description:</strong> ${event.description || "No description"}<br><br>`
                )
                .join("");

            eventDetails.style.display = "block";
            eventDetails.innerHTML = eventList;

            // Show both buttons: Remove and Add More Event
            removeEventButton.style.display = "block"; // Keep "Remove Event" button
            const addMoreButton = document.createElement("button");
            addMoreButton.textContent = "Add More Event";
            addMoreButton.id = "addMoreEventButton";
            addMoreButton.onclick = () => showAddEventForm();
            if (!document.getElementById("addMoreEventButton")) {
                eventDetails.appendChild(addMoreButton);
            }

            eventForm.style.display = "none"; // Hide the form initially
            eventBoxTitle.textContent = "Event Details";
        } else {
            // No events on this date; add a new event
            eventDetails.style.display = "none";
            removeEventButton.style.display = "none"; // Hide "Remove Event" button
            eventForm.style.display = "block";
            eventBoxTitle.textContent = "Add Event";
            document.getElementById("startDate").value = date;
        }
    }


    // Add more event
    function addMoreEvent() {
        eventForm.style.display = "block";
        eventDetails.style.display = "none";
        eventBoxTitle.textContent = "Add Another Event";
    }

    // Close event box
    function closeBox() {
        eventBox.style.display = "none";
        overlay.style.display = "none";
    }

    // // Save event
    // eventForm.addEventListener("submit", (e) => {
    //     e.preventDefault();

    //     const eventName = document.getElementById("eventName").value;
    //     const startDate = document.getElementById("startDate").value;
    //     const endDate = document.getElementById("endDate").value || startDate;
    //     const startTime = document.getElementById("startTime").value;
    //     const endTime = document.getElementById("endTime").value;
    //     const eventDescription = document.getElementById("eventDescription").value;

    //     const dateKey = startDate;

    //     if (!events[dateKey]) {
    //         events[dateKey] = [];
    //     }

    //     events[dateKey].push({ name: eventName, startDate, endDate, startTime, endTime, description: eventDescription });
    //     closeBox();
    //     generateCalendar(new Date().getFullYear(), new Date().getMonth());
    // });

    // Remove event
    function removeEvent() {
        delete events[currentDate];
        closeBox();
        generateCalendar(new Date().getFullYear(), new Date().getMonth());
    }

    // Initialize calendar
    generateCalendar(new Date().getFullYear(), new Date().getMonth());

    // Remove event
    function removeEvent() {
        const dateKey = currentDate;

        if (events[dateKey] && events[dateKey].length > 0) {
            // Hide unnecessary buttons during the removal process
            removeEventButton.style.display = "none";
            const addMoreButton = document.getElementById("addMoreEventButton");
            if (addMoreButton) addMoreButton.style.display = "none";

            // Show a dropdown to select the event to remove
            const eventOptions = events[dateKey]
                .map(
                    (event, index) =>
                        `<option value="${index}">${event.name} (${event.startTime} - ${event.endTime})</option>`
                )
                .join("");

            const selectHTML = `
                <label for="eventSelect">Select Event to Remove:</label>
                <select id="eventSelect">${eventOptions}</select>
                <div style="margin-top: 15px;">
                    <button id="confirmRemoveEvent">Confirm Remove</button>
                    
                </div>
            `;

            // Replace event details content with selection options
            eventDetails.style.display = "block";
            eventDetails.innerHTML = selectHTML;

            // Hide the event form
            eventForm.style.display = "none";

            // Add click handler for confirming the removal
            document
                .getElementById("confirmRemoveEvent")
                .addEventListener("click", () => {
                    const selectedIndex = document.getElementById("eventSelect").value;
                    events[dateKey].splice(selectedIndex, 1); // Remove the selected event

                    // If no events remain, delete the key
                    if (events[dateKey].length === 0) {
                        delete events[dateKey];
                    }

                    closeBox();
                    generateCalendar(new Date().getFullYear(), new Date().getMonth());
                });

            // Add click handler for canceling the removal
            document
                .getElementById("cancelRemoveEvent")
                .addEventListener("click", () => {
                    // Close the event box without making changes
                    closeBox();
                });
        } else {
            alert("No events to remove on this date.");
        }
    }



    // Show add event form
    function showAddEventForm() {
        eventDetails.style.display = "none";
        removeEventButton.style.display = "none"; // Hide "Remove Event" button
        const addMoreButton = document.getElementById("addMoreEventButton");
        if (addMoreButton) addMoreButton.style.display = "none"; // Hide "Add More Event" button
        eventForm.style.display = "block"; // Show the form
        eventBoxTitle.textContent = "Add Another Event";
    }



    // Event listeners for navigation
    prevMonth.addEventListener("click", () => {
        currentMonth -= 1;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear -= 1;
        }
        populateSelectors();
        generateCalendar(currentYear, currentMonth);
    });

    nextMonth.addEventListener("click", () => {
        currentMonth += 1;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear += 1;
        }
        populateSelectors();
        generateCalendar(currentYear, currentMonth);
    });

    monthSelector.addEventListener("change", (e) => {
        currentMonth = parseInt(e.target.value, 10);
        generateCalendar(currentYear, currentMonth);
    });

    yearSelector.addEventListener("change", (e) => {
        currentYear = parseInt(e.target.value, 10);
        generateCalendar(currentYear, currentMonth);
    });

    // Initialize calendar
    populateSelectors();
    generateCalendar(currentYear, currentMonth);



</script>

</body>
</html>
