FROM nginx:alpine

# Copy custom nginx configuration
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Create directory for application
RUN mkdir -p /var/www/html

# Expose port 80
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
