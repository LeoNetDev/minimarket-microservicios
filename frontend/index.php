
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minimarket | Gestión de productos</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        header {
            background: linear-gradient(135deg, #12372a, #1c6546);
            color: white;
            padding: 25px 6%;
        }

        header h1 {
            margin: 0 0 8px;
        }

        header p {
            margin: 0;
            color: #d1fae5;
        }

        main {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .panel {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px #0000000a;
        }

        h2 {
            margin-top: 0;
            font-size: 21px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        label {
            display: block;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 7px;
        }

        input {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
        }

        input:focus {
            outline: 2px solid #86efac;
            border-color: #15803d;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        button {
            border: 0;
            padding: 10px 15px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: bold;
        }

        button:disabled {
            opacity: .6;
            cursor: wait;
        }

        .primary {
            background: #15803d;
            color: white;
        }

        .secondary {
            background: #e2e8f0;
            color: #334155;
        }

        .edit {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .delete {
            background: #fee2e2;
            color: #b91c1c;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        th, td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;
            font-size: 13px;
        }

        td {
            font-size: 14px;
        }

        .status {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 7px;
            display: none;
        }

        .success {
            display: block;
            background: #dcfce7;
            color: #166534;
        }

        .error {
            display: block;
            background: #fee2e2;
            color: #991b1b;
        }

        .empty {
            text-align: center;
            color: #64748b;
            padding: 25px;
        }

        @media (max-width: 750px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>Minimarket Central</h1>
    <p>Sistema de gestión y control de productos</p>
</header>

<main>

    <div id="mensaje" class="status" role="status"
         aria-live="polite"></div>

    <section class="panel">
        <h2 id="tituloFormulario">Registrar producto</h2>

        <form id="formProducto">
            <input type="hidden" id="id">

            <div class="form-grid">
                <div>
                    <label for="nombre">Nombre del producto</label>
                    <input id="nombre" maxlength="100" required>
                </div>

                <div>
                    <label for="proveedor">Proveedor</label>
                    <input id="proveedor" maxlength="100" required>
                </div>

                <div>
                    <label for="fecha_vencimiento">Fecha de vencimiento</label>
                    <input type="date" id="fecha_vencimiento" required>
                </div>

                <div>
                    <label for="precio">Precio (Bs)</label>
                    <input type="number" id="precio"
                           min="0" max="99999999.99"
                           step="0.01" required>
                </div>

                <div>
                    <label for="stock">Cantidad en stock</label>
                    <input type="number" id="stock"
                           min="0" max="2147483647"
                           step="1" required>
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="primary" id="btnGuardar">
                    Registrar producto
                </button>

                <button type="button" class="secondary"
                        id="btnCancelar" hidden>
                    Cancelar edición
                </button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Inventario de productos</h2>
        <p>Productos activos registrados en el sistema.</p>

        <div class="actions">
            <button type="button" class="secondary"
                    id="btnActualizar">
                Actualizar listado
            </button>
        </div>

        <div class="table-wrap" style="margin-top: 18px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Proveedor</th>
                        <th>Vencimiento</th>
                        <th>Precio (Bs)</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaProductos">
                    <tr>
                        <td colspan="7" class="empty">
                            Cargando productos...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

</main>

<script>
const API = "/minimarket/backend/api/productos.php";

const formulario = document.getElementById("formProducto");
const tabla = document.getElementById("tablaProductos");
const mensaje = document.getElementById("mensaje");
const btnGuardar = document.getElementById("btnGuardar");
const btnCancelar = document.getElementById("btnCancelar");

let productos = [];
let enviando = false;

function mostrarMensaje(texto, tipo = "success") {
    mensaje.textContent = texto;
    mensaje.className = "status " + tipo;
}

async function solicitud(url, metodo = "GET", datos = null) {
    const opciones = {
        method: metodo,
        headers: {
            "Accept": "application/json"
        }
    };

    if (datos !== null) {
        opciones.headers["Content-Type"] = "application/json";
        opciones.body = JSON.stringify(datos);
    }

    const respuesta = await fetch(url, opciones);
    const texto = await respuesta.text();

    let resultado;

    try {
        resultado = texto ? JSON.parse(texto) : null;
    } catch {
        throw new Error("La API no devolvió una respuesta JSON válida.");
    }

    if (!respuesta.ok) {
        throw new Error(
            resultado?.error || "Error HTTP " + respuesta.status
        );
    }

    return resultado;
}

function celda(fila, valor) {
    const td = document.createElement("td");
    td.textContent = valor ?? "";
    fila.appendChild(td);
    return td;
}

async function cargarProductos() {
    tabla.replaceChildren();

    const filaCarga = document.createElement("tr");
    const tdCarga = document.createElement("td");
    tdCarga.colSpan = 7;
    tdCarga.className = "empty";
    tdCarga.textContent = "Consultando productos...";
    filaCarga.appendChild(tdCarga);
    tabla.appendChild(filaCarga);

    try {
        const resultado = await solicitud(API);

        if (!Array.isArray(resultado)) {
            throw new Error("La API no devolvió una lista de productos.");
        }

        productos = resultado;
        tabla.replaceChildren();

        if (productos.length === 0) {
            const fila = document.createElement("tr");
            const td = document.createElement("td");
            td.colSpan = 7;
            td.className = "empty";
            td.textContent = "No hay productos activos registrados.";
            fila.appendChild(td);
            tabla.appendChild(fila);
            return;
        }

        productos.forEach(producto => {
            const fila = document.createElement("tr");

            celda(fila, producto.id);
            celda(fila, producto.nombre);
            celda(fila, producto.proveedor);
            celda(fila, producto.fecha_vencimiento);
            celda(fila, Number(producto.precio).toFixed(2));
            celda(fila, producto.stock);

            const tdAcciones = document.createElement("td");

            const btnEditar = document.createElement("button");
            btnEditar.type = "button";
            btnEditar.className = "edit";
            btnEditar.textContent = "Editar";
            btnEditar.addEventListener("click", () => editarProducto(producto));

            const btnEliminar = document.createElement("button");
            btnEliminar.type = "button";
            btnEliminar.className = "delete";
            btnEliminar.textContent = "Eliminar";
            btnEliminar.style.marginLeft = "6px";
            btnEliminar.addEventListener("click", () => eliminarProducto(producto));

            tdAcciones.append(btnEditar, btnEliminar);
            fila.appendChild(tdAcciones);
            tabla.appendChild(fila);
        });

    } catch (error) {
        tabla.replaceChildren();

        const fila = document.createElement("tr");
        const td = document.createElement("td");
        td.colSpan = 7;
        td.className = "empty";
        td.textContent = "No se pudo cargar: " + error.message;

        fila.appendChild(td);
        tabla.appendChild(fila);
    }
}

function editarProducto(producto) {
    document.getElementById("id").value = producto.id;
    document.getElementById("nombre").value = producto.nombre;
    document.getElementById("proveedor").value = producto.proveedor;
    document.getElementById("fecha_vencimiento").value =
        producto.fecha_vencimiento;
    document.getElementById("precio").value = producto.precio;
    document.getElementById("stock").value = producto.stock;

    document.getElementById("tituloFormulario").textContent =
        "Editar producto #" + producto.id;

    btnGuardar.textContent = "Guardar cambios";
    btnCancelar.hidden = false;

    formulario.scrollIntoView({ behavior: "smooth", block: "start" });
}

function limpiarFormulario() {
    formulario.reset();
    document.getElementById("id").value = "";
    document.getElementById("tituloFormulario").textContent =
        "Registrar producto";
    btnGuardar.textContent = "Registrar producto";
    btnCancelar.hidden = true;
}

btnCancelar.addEventListener("click", limpiarFormulario);

formulario.addEventListener("submit", async evento => {
    evento.preventDefault();

    if (enviando) return;

    const id = document.getElementById("id").value;

    const producto = {
        nombre: document.getElementById("nombre").value.trim(),
        proveedor: document.getElementById("proveedor").value.trim(),
        fecha_vencimiento:
            document.getElementById("fecha_vencimiento").value,
        precio: document.getElementById("precio").value,
        stock: document.getElementById("stock").value
    };

    if (!producto.nombre || !producto.proveedor ||
        !producto.fecha_vencimiento ||
        producto.precio === "" || producto.stock === "") {
        mostrarMensaje("Completa todos los campos.", "error");
        return;
    }

    if (!Number.isFinite(Number(producto.precio)) ||
        Number(producto.precio) < 0 ||
        Number(producto.precio) > 99999999.99) {
        mostrarMensaje("Introduce un precio válido.", "error");
        return;
    }

    if (!Number.isSafeInteger(Number(producto.stock)) ||
        Number(producto.stock) < 0 ||
        Number(producto.stock) > 2147483647) {
        mostrarMensaje("Introduce un stock entero válido.", "error");
        return;
    }

    enviando = true;
    btnGuardar.disabled = true;

    try {
        const resultado = await solicitud(
            id ? API + "?id=" + encodeURIComponent(id) : API,
            id ? "PUT" : "POST",
            producto
        );

        mostrarMensaje(resultado.mensaje || "Operación completada.");
        limpiarFormulario();
        await cargarProductos();

    } catch (error) {
        mostrarMensaje(error.message, "error");
    } finally {
        enviando = false;
        btnGuardar.disabled = false;
    }
});

async function eliminarProducto(producto) {
    const confirmar = confirm(
        "¿Deseas eliminar lógicamente el producto " +
        producto.nombre + "?\nEl registro se conservará en MySQL."
    );

    if (!confirmar) return;

    try {
        const resultado = await solicitud(
            API + "?id=" + encodeURIComponent(producto.id),
            "DELETE"
        );

        mostrarMensaje(resultado.mensaje || "Producto eliminado.");
        await cargarProductos();

    } catch (error) {
        mostrarMensaje(error.message, "error");
    }
}

document.getElementById("btnActualizar").addEventListener(
    "click", cargarProductos
);

cargarProductos();
</script>

</body>
</html>
