import React from "react";
import { connect } from "react-redux";

let ws = null;
let currencies = {};

class CurrencyData extends React.PureComponent {
  state = {
    orderBookChannelId: null,
    tradesChannelId: null
  };

  componentDidMount() {
    ws = this.connectWs();
  }

  componentWillUnmount() {
    this.closeWs();
  }

  connectWs = () => {
    ws = new WebSocket("wss://findiri.com:7000/api/v1/ws/rate");

    ws.onopen = this.onWsOpen;
    ws.onmessage = this.onWsMessage;
    ws.onclose = this.onWsClose;
    ws.onerror = error => console.error(error);

    return ws;
  };

  onWsOpen = () => console.log("WS opened");

  onWsMessage = message => {
    const data = JSON.parse(message.data);
    currencies[data.pair] = data.price;
  };

  onWsClose = () => console.log("WS closed");

  closeWs = () => ws.close();

  render() {
    return null;
  }
}

export default connect()(CurrencyData);
