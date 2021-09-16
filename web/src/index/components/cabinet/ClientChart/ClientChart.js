import React from "react";

import Chart from "../Chart/Chart";
import EmptyContentBlock from "../EmptyContentBlock/EmptyContentBlock";
import * as utils from "../../../../utils";

class ClientChart extends React.Component {
  render() {
    if (!this.props.chart.data.length) {
      return (
        <EmptyContentBlock
          icon={require("../../../../asset/120/trade.svg")}
          message={utils.getLang("cabinet_placeholder_investmentsProfitCharts")}
        />
      );
    }

    let clients = this.props.chart.data.map(item => ({
      x: parseInt(item.date) * 1000,
      y: item.count,
      title: item.count + " people" //item.amount.toFixed(8) + ' ' + item.currency.toUpperCase()
    }));

    let marker = false;
    if (this.props.chart.data.length === 1) {
      marker = true;
    }

    return (
      <div className="Content_box Chart__profit">
        <div className="Chart__profit__header">
          <div className="Chart__profit__header__cont">
            <h3>{this.props.title}</h3>
            <div className="Chart__profit__header__period">
              30 {utils.getLang("global_days")}
            </div>
          </div>
          <div className="Chart__profit__header__fiat">
            {this.props.chart.total}
          </div>
        </div>
        <div className="Chart__profit__chart">
          <Chart
            marker={marker}
            count={Object.keys(this.props.chart.data).length}
            series={[
              {
                data: clients,
                name: "Clients",
                type: "spline",
                showInLegend: false,
                color: "var(--green)",
                tooltip: {
                  valueDecimals: 2
                },
                shadow: {
                  color: "var(--green)"
                }
              }
            ]}
            adaptive={this.props.adaptive}
          />
        </div>
      </div>
    );
  }
}

export default ClientChart;
