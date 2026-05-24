// Get DOM elements
const calendar = document.getElementById("calendar");
const monthYear = document.getElementById("monthYear");

// Current date tracker
let date = new Date();

// Store backend data
let data = {};

/* FETCH DATA FROM BACKEND*/
async function loadData() {
    try {
        // Fetch data from backend
        const res = await fetch("../calendar/calendar.php");

        // Convert response to text (debug-safe)
        const text = await res.text();
        console.log("RAW DATA:", text);

        // Convert to JSON
        data = text ? JSON.parse(text) : {};

        console.log("PARSED DATA:", data);

    } catch (error) {
        console.error("Error fetching data:", error);
        data = {}; // fallback
    }
}

/* RENDER CALENDAR (MONTH VIEW)*/
function renderCalendar() {

    // Clear existing calendar
    calendar.innerHTML = "";

    const year = date.getFullYear();
    const month = date.getMonth();

    // Month names
    const months = [
        "January","February","March","April","May","June",
        "July","August","September","October","November","December"
    ];

    // Update header
    monthYear.innerText = `${months[month]} ${year}`;

    // Get first day and total days
    const firstDay = new Date(year, month, 1).getDay();
    const lastDate = new Date(year, month + 1, 0).getDate();

    /*  Empty spaces before 1st day */
    for (let i = 0; i < firstDay; i++) {
        calendar.innerHTML += `<div></div>`;
    }

    /*Render each day */
    for (let i = 1; i <= lastDate; i++) {

        // Format date (YYYY-MM-DD)
        let fullDate = `${year}-${String(month+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;

        let events = "";

        // Check if data exists for this date
        if (data[fullDate]) {

            data[fullDate].forEach(e => {

                if (e.type === "income") {
                    events += `<div class="event income">💰 $${e.amount}</div>`;
                }

                if (e.type === "expense") {
                    events += `<div class="event expense">💸 $${e.amount}</div>`;
                }

                if (e.type === "goal") {
                    events += `<div class="event goal">🎯 ${e.title}</div>`;
                }

            });
        }

        // Add day to calendar
        calendar.innerHTML += `
            <div class="day" onclick="showDetails('${fullDate}')">
                <span>${i}</span>
                ${events}
            </div>
        `;
    }
}

/*SHOW POPUP */
function showDetails(selectedDate) {

    const popup = document.getElementById("popup");
    const content = document.getElementById("popupContent");

    let html = `<h3>${selectedDate}</h3>`;

    if (data[selectedDate]) {

        data[selectedDate].forEach(e => {

            /*INCOME */
            if (e.type === "income") {
                html += `
                    <div class="popup-item income">
                        <strong>💰 Income</strong>
                        <p>Amount: $${e.amount}</p>
                        <p>Description: ${e.description}</p>
                        <p>Date: ${e.date}</p>
                    </div>
                `;
            }

            /* EXPENSE */
            if (e.type === "expense") {
                html += `
                    <div class="popup-item expense">
                        <strong>💸 Expense</strong>
                        <p>Amount: $${e.amount}</p>
                        <p>Description: ${e.description}</p>
                        <p>Date: ${e.date}</p>
                    </div>
                `;
            }

            /* GOAL*/
            if (e.type === "goal") {
                html += `
                    <div class="popup-item goal">
                        <strong>🎯 Goal</strong>
                        <p>Name: ${e.title}</p>
                        <p>Target: $${e.target}</p>
                        <p>Saved: $${e.saved}</p>
                        <p>Remaining: $${e.remaining}</p>
                        <p>Deadline: ${e.deadline}</p>
                    </div>
                `;
            }

        });

    } else {
        html += `<p>No records found</p>`;
    }

    content.innerHTML = html;
    popup.style.display = "flex";
}

/*CLOSE POPUP*/
function closePopup() {
    document.getElementById("popup").style.display = "none";
}

/*  MONTH NAVIGATION */
document.getElementById("prev").onclick = () => {
    date.setMonth(date.getMonth() - 1);
    renderCalendar();
};

document.getElementById("next").onclick = () => {
    date.setMonth(date.getMonth() + 1);
    renderCalendar();
};

/* INITIAL LOAD*/
loadData().then(() => {
    renderCalendar();
});