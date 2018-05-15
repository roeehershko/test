# Set nginx base image
FROM nginx

# Copy custom configuration file from the current directory
COPY nginx.conf /etc/nginx/nginx.conf
COPY gts-con.pnc.co.il.crt /etc/nginx/gts-con.pnc.co.il.crt
COPY gts-con.pnc.co.il.key /etc/nginx/gts-con.pnc.co.il.key

EXPOSE 443