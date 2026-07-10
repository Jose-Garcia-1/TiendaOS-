# 🛒 TiendaOS

> Plataforma de e-commerce TiendaOS con arquitectura en capas y monitoreo de recursos del Sistema Operativo. (Proyecto académico para Sistemas Operativos 2026-1).

## 📚 Tecnologías y Arquitectura

*   **Backend:** PHP 8 (MVC), MySQL 8.
*   **Frontend:** HTML5, CSS3, JavaScript (Vanilla).
*   **Microservicios:** Node.js + Express + MySQL2 (Dockerizado para el sistema de reseñas).
*   **Infraestructura Cloud:** Desplegado en **AWS EC2** (América del Norte).
    *   🐧 **Servidor 1 (Linux):** Ubuntu 24.04 LTS (Aloja Docker, Mongodby la Base de Datos).
    *   🪟 **Servidor 2 (Windows):** Windows Server 2022 (Aloja el E-commerce y el servicio de monitoreo de recursos del SO).

## 🧠 Conceptos de Sistemas Operativos Aplicados

Este proyecto destaca por la integración de conceptos fundamentales de Sistemas Operativos en un entorno cloud real:

1. **Llamadas al Sistema (Interacción con Kernel):**
   *   Se utiliza `shell_exec()` en PHP para interactuar directamente con la terminal del servidor.
   *   En **Linux** se ejecutan comandos como `uptime`, `free -h`, `df -h`, `ps aux`.
   *   En **Windows** se ejecutan comandos como `wmic cpu get loadpercentage`, `tasklist`.
   *   Esto permite mostrar en tiempo real el estado de la CPU, memoria, disco y procesos en el Panel de Administración.

2. **Procesos y Concurrencia:**
   *   El sistema de **Reseñas** funciona como un microservicio aislado en un contenedor **Docker**, ejecutándose como un proceso independiente de la aplicación web principal (Node.js).
   *   Se implementa comunicación entre procesos vía HTTP (`resenas_proxy.php` consulta al contenedor en `localhost:3000`).

3. **Sincronización (MutEx):**
   *   Para evitar condiciones de carrera en la compra de productos, se utiliza el comando SQL **`SELECT FOR UPDATE`** dentro de una transacción en el procesamiento de pedidos. Esto bloquea la fila del producto en la base de datos asegurando que dos usuarios no puedan comprar el mismo stock al mismo tiempo.

4. **Sistemas Operativos Heterogéneos:**
   *   El proyecto demuestra una arquitectura de red entre dos sistemas operativos distintos (Linux y Windows).
   *   El servidor **Windows** expone una API REST simple (`recursos_windows.php`) que es consumida por el servidor **Linux** para mostrar las estadísticas de Windows en el panel de control de TiendaOS.

## ✨ Características Principales del E-commerce

*   ✅ **Gestión de Usuarios:** Registro, inicio de sesión (hashing Bcrypt) y roles (Cliente / Admin).
*   ✅ **Catálogo:** Filtros por búsqueda y categorías, con imágenes dinámicas.
*   ✅ **Carrito de Compras:** Persistente en Base de Datos, permite sumar/restar cantidades y eliminar productos.
*   ✅ **Procesamiento de Pedidos:** Checkout completo, actualización automática de stock y registro en el historial.
*   ✅ **Panel Administrativo:** CRUD de productos, gestión de roles de usuarios y **Monitoreo de Recursos del SO** (Linux + Windows).
*   ✅ **Sistema de Reseñas:** Servicio desplegado en contenedor Docker, validación de compras previas y calificación con estrellas.

## 🚀 Despliegue (Entorno Cloud)

El sistema fue desplegado en **AWS EC2** utilizando el modelo de 3 capas (Presentación, Aplicación y Datos). Ambas instancias utilizan el tipo `t3.micro` (Free Tier) y se comunican entre sí a través de sus respectivas IPs privadas/públicas dentro de la misma VPC y grupo de seguridad.

---

⭐ *Proyecto desarrollado para el curso de Sistemas Operativos.*
