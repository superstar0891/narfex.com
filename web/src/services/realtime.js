import * as auth from "./auth";
import * as utils from "../utils";

class RealTime {
  constructor() {
    const token = auth.getToken();

    const LOCAL_EXCHANGE_ENDPOINT =
      process.env.REACT_APP_LOCAL_EXCHANGE_WS_ENDPOINT;
    if (LOCAL_EXCHANGE_ENDPOINT) {
      this.endpoint = LOCAL_EXCHANGE_ENDPOINT;
    } else {
      if (utils.isProduction()) {
        this.endpoint = "wss://ex.narfex.dev";
      } else {
        this.endpoint = "wss://api-stage.narfex.dev/echo";
      }
    }
    this.endpoint += token ? `?access_token=${token}` : "";
    this.listeners = {};
    this.sendQueue = [];
    this.connected = false;
    this.connection = null;
    this.subscribtions = {};
    this.reconnectTimeout = 0;

    this.__connect();
  }

  __connect = () => {
    // setTimeout(() => {
    //   this.connection.close();
    // }, 20000);

    this.connected = false;
    this.connection = new WebSocket(this.endpoint);

    this.connection.onopen = () => {
      this.connected = true;
      console.log("[WS] Connected");
      this.reconnectTimeout = 0;

      // resolve queue
      for (let event of this.sendQueue) {
        this.connection.send(JSON.stringify(event));
      }

      this.__restoreSubscriptions();
      this.triggerListeners("open_connection");
    };

    this.connection.onerror = error => {
      console.log("[WS] Error: ", error.message);
      this.triggerListeners("error_connection");
    };

    this.connection.onclose = () => {
      console.log("[WS] Close");
      // this.connected = false;
      this.triggerListeners("close_connection");
      setTimeout(this.__connect, this.reconnectTimeout);

      this.reconnectTimeout =
        this.reconnectTimeout < 6000
          ? (this.reconnectTimeout || 1000) * 1.62
          : this.reconnectTimeout;
    };

    this.connection.onmessage = this.__messageDidReceive;
  };

  __messageDidReceive = ({ data }) => {
    let messages = data.split("\n");

    for (let message of messages) {
      let json;
      try {
        json = JSON.parse(message);
      } catch (e) {
        console.log("[WS] Error:", e.message, message);
        continue;
      }

      //console.log('[WS]', json);
      if (this.listeners[json.type]) {
        for (let listener of this.listeners[json.type]) {
          listener(json.body);
        }
      }
    }
  };

  __send(action, params = {}) {
    const event = { action, params };

    if (!this.connected) {
      this.sendQueue.push(event);
    } else {
      this.connection.send(JSON.stringify(event));
    }
  }

  addListener(name, callback) {
    if (!this.listeners[name]) {
      this.listeners[name] = [];
    }

    this.listeners[name].push(callback);

    if (this.connected && name === "open_connection") {
      this.triggerListeners(name);
    }
  }

  removeListener(name, callback) {
    if (!this.listeners[name]) {
      return;
    }

    for (let i = 0; i < this.listeners[name].length; i++) {
      if (this.listeners[name][i] === callback) {
        this.listeners[name].splice(i, 1);
      }
    }
  }

  triggerListeners(name, data = {}) {
    console.log("triggerListeners", name);
    if (!this.listeners[name]) {
      return;
    }

    for (let listener of this.listeners[name]) {
      listener(data);
    }
  }

  subscribe(channel) {
    this.subscribtions[channel] = true;
    this.__send("subscribe", { channel });
  }

  unsubscribe(channel) {
    delete this.subscribtions[channel];
    this.__send("unsubscribe", { channel });
  }

  __restoreSubscriptions() {
    for (let channel in this.subscribtions) {
      this.subscribe(channel);
    }
  }
}

export let shared;

export default function init() {
  shared = new RealTime();
}
