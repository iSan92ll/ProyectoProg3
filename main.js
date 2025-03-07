document.addEventListener("DOMContentLoaded", () => {
    cargarProductos();
    document.getElementById("btnAgregar").addEventListener("click", agregarProducto);
});

function cargarProductos() {
    fetch("http://localhost/ProyectoProgramacion3/backend.php?action=read")
    .then(response => response.json())
    .then(data => {
            let listado = document.getElementById("listado");
            listado.innerHTML = "";
            data.forEach(producto => {
                listado.innerHTML += `
                <tr>
                <td class="text-center">${producto.id}</td>
                <td class="text-center">${producto.producto}</td>
                <td class="text-center">${producto.precio}</td>
                <td class="text-center">${producto.disponibilidad}</td>
                <td class="text-center">
                <button class="btn btn-warning btn-sm" onclick="editarProducto(${producto.id}, '${producto.producto}', ${producto.precio}, '${producto.disponibilidad}')">Editar</button>
                <button class="btn btn-danger btn-sm" onclick="eliminarProducto(${producto.id})">Eliminar</button>
                </td>
                </tr>`;
            });
        })
        .catch(error => console.error("Error al cargar productos:", error));
    }
    
    function agregarProducto() {
    let producto = document.getElementById("producto").value;
    let precio = document.getElementById("precio").value;
    let disponibilidad = document.getElementById("disponibilidad").value;
    
    if (!producto || !precio || !disponibilidad) {
        alertify.error("Todos los campos son obligatorios");
        return;
    }
    
    let formData = new FormData();
    formData.append("producto", producto);
    formData.append("precio", precio);
    formData.append("disponibilidad", disponibilidad);

    fetch("http://localhost/ProyectoProgramacion3/backend.php?action=create", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => console.log(data))
    .then(() => {
        alertify.success("Producto agregado");
        cargarProductos();
        document.getElementById("producto").value = "";
        document.getElementById("precio").value = "";
        document.getElementById("disponibilidad").value = "";
    });
}

function editarProducto(id, nombre, precio, disponibilidad) {
    document.getElementById("producto").value = nombre;
    document.getElementById("precio").value = precio;
    document.getElementById("disponibilidad").value = disponibilidad;
    document.getElementById("btnAgregar").innerHTML = "Actualizar";
    document.getElementById("btnAgregar").removeEventListener("click", agregarProducto);
    document.getElementById("btnAgregar").addEventListener("click", () => actualizarProducto(id));
}

function actualizarProducto(id) {
    let producto = document.getElementById("producto").value;
    let precio = document.getElementById("precio").value;
    let disponibilidad = document.getElementById("disponibilidad").value;
    
    fetch("http://localhost/ProyectoProgramacion3/backend.php?action=update", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id=${id}&producto=${producto}&precio=${precio}&disponibilidad=${disponibilidad}`
    }).then(() => {
        alertify.success("Producto actualizado");
        cargarProductos();
        document.getElementById("producto").value = "";
        document.getElementById("precio").value = "";
        document.getElementById("disponibilidad").value = "";
        document.getElementById("btnAgregar").innerHTML = "Agregar";
        let nuevoBtn = document.getElementById("btnAgregar").cloneNode(true);
        document.getElementById("btnAgregar").parentNode.replaceChild(nuevoBtn, document.getElementById("btnAgregar"));
        nuevoBtn.addEventListener("click", agregarProducto);
    });
}

function eliminarProducto(id) {
    if (confirm("¿Estás seguro de eliminar este producto?")) {
        fetch("http://localhost/ProyectoProgramacion3/backend.php?action=delete", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id=${id}`
        }).then(() => {
            alertify.error("Producto eliminado");
            cargarProductos();
        });
    }
}
