body {
font-family: 'Poppins', sans-serif;
background-color: #f4f5f7;
}

#wrapper {
display: flex;
min-height: 100vh;
}

#sidebar-wrapper {
width: 220px;
background-color: #2c3e50;
color: #ecf0f1;
display: flex;
flex-direction: column;
}

.sidebar-heading {
padding: 1.5rem 1rem;
font-size: 1.1rem;
font-weight: 700;
text-align: center;
border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.list-group-item {
background: transparent;
color: #ecf0f1;
border: none;
display: flex;
align-items: center;
gap: 10px;
padding: 12px 20px;
border-bottom: 1px solid rgba(236, 240, 241, 0.1);
transition: 0.2s;
}

.list-group-item i {
width: 20px;
text-align: center;
color: #ecf0f1;
}

.list-group-item:hover {
background-color: rgba(236, 240, 241, 0.1);
color: #fff;
}

#page-content-wrapper {
flex: 1;
padding: 30px;
}

.navbar {
border-radius: 0.5rem;
}

.card {
border-radius: 0.75rem;
box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
transition: transform 0.2s;
position: relative;
overflow: hidden;
}

.card:hover {
transform: translateY(-3px);
}

.card h5 {
font-weight: 600;
}

.card p {
font-weight: 500;
}

.bg-total {
background: linear-gradient(135deg, #5dade2, #48c9b0);
color: #fff;
}

.bg-baik {
background: linear-gradient(135deg, #2ecc71, #27ae60);
color: #fff;
}

.bg-perawatan {
background: linear-gradient(135deg, #f1c40f, #f39c12);
color: #2c3e50;
}

.bg-rusak {
background: linear-gradient(135deg, #e74c3c, #c0392b);
color: #fff;
}

.status-dot {
width: 12px;
height: 12px;
border-radius: 50%;
display: inline-block;
margin-right: 8px;
}

table.table-hover tbody tr:hover {
background-color: #ecf0f1;
}

.card-header {
font-weight: 600;
background-color: #34495e;
color: #ecf0f1;
}

@media (max-width:768px) {
#sidebar-wrapper {
width: 180px;
}
}