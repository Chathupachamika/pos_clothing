import { ref, onMounted, onUnmounted } from "vue";
import { createConnection, subscribeEntities } from "home-assistant-js-websocket";

export function useHass() {
  const hass = ref(null);
  const entities = ref({});
  const connected = ref(false);
  const error = ref(null);

  const connect = async () => {
    try {
      const baseUrl = import.meta.env.VITE_HASS_URL;
      const authToken = import.meta.env.VITE_HASS_TOKEN;

      console.log('HASS Config:', { 
        baseUrl, 
        hasToken: !!authToken 
      });

      // Convert to WebSocket URL and ensure proper format
      const hassUrl = baseUrl.replace(/^http/, 'ws')
                            .replace(/\/$/, '')
                            + '/api/websocket';

      console.log('Attempting HASS connection to:', hassUrl);

      const conn = await createConnection({
        auth: authToken,
        hassUrl: hassUrl
      });

      hass.value = conn;
      connected.value = true;
      error.value = null;
      console.log('HASS Connected successfully');

      subscribeEntities(conn, (newEntities) => {
        entities.value = newEntities;
      });
    } catch (err) {
      console.error("HASS Connection Error Details:", {
        message: err.message,
        stack: err.stack,
        type: err.constructor.name
      });
      error.value = `Connection failed: ${err.message}`;
      connected.value = false;
    }
  };

  // Add reconnection logic
  let reconnectTimer = null;
  const attemptReconnect = () => {
    if (!connected.value && !reconnectTimer) {
      reconnectTimer = setTimeout(() => {
        console.log('Attempting to reconnect to HASS...');
        connect();
        reconnectTimer = null;
      }, 5000);
    }
  };

  onMounted(() => {
    connect();
  });

  onUnmounted(() => {
    if (hass.value) {
      hass.value.close();
    }
    hass.value = null;
    connected.value = false;
    if (reconnectTimer) {
      clearTimeout(reconnectTimer);
    }
  });

  return { hass, entities, connected, error, connect };
}
