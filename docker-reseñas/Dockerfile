# 1. Se utiliza la imagen oficial de Node.js basada en Alpine Linux.
#    Alpine es una distribución extremadamente ligera (reduce el tamaño de la imagen final).
FROM node:18-alpine

# 2. Se establece el directorio de trabajo dentro del contenedor.
#    Todos los comandos posteriores se ejecutarán en esta ruta.
WORKDIR /app

# 3. Se copia exclusivamente el archivo de manifiesto de dependencias (package.json)
#    para aprovechar la caché de Docker. Si no hay cambios en las dependencias,
#    la instalación de paquetes se omite en futuras construcciones, acelerando el build.
COPY package.json .
RUN npm install

# 4. Una vez instaladas las dependencias, se copia el código fuente de la aplicación.
COPY server.js .

# 5. Se indica al contenedor que escuche en el puerto 3000 para peticiones entrantes.
EXPOSE 3000

# 6. Se define el comando de inicio del contenedor.
#    Al arrancar, ejecutará el servidor Node.js definido en server.js.
CMD ["node", "server.js"]