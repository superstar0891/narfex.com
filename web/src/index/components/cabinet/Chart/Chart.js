/* eslint-disable */
import "./Chart.less";

import React, { useState } from "react";
import PropTypes from "prop-types";
import Highcharts from "highcharts";
import HighchartsReact from "highcharts-react-official";
import { classNames, dateFormat, getCssVar } from "../../../../utils/index";
import { getCurrencyInfo } from "../../../../actions";

export default function Chart({ series, ...props }) {
  const [hovered, setHovered] = useState(false);

  const options = {
    chart: {
      height: 200,
      backgroundColor: "transparent"
    },
    title: {
      text: undefined
    },
    subtitle: {
      text: undefined
    },
    xAxis: {
      type: "datetime",
      title: false,
      subtitle: false,
      gridLineWidth: 0,
      lineWidth: 0,
      minorGridLineWidth: 0,
      lineColor: "transparent",
      minorTickLength: 0,
      tickLength: 0,
      labels: {
        y: 19,
        distance: 0,
        padding: 0,
        style: {
          fontSize: "11px",
          fontWeight: 500,
          lineHeight: "16px",
          color: getCssVar("--gray", "#808080"),
          fontFamily: "Montserrat"
        }
      },
      crosshair: {
        color: getCssVar("--gray", "#808080")
      }
    },
    yAxis: {
      title: false,
      opposite: true,
      gridLineWidth: 0,
      subtitle: false,
      labels: {
        enabled: false
      }
    },
    legend: {
      useHTML: true,
      symbolPadding: 0,
      symbolWidth: 0,
      symbolRadius: 0,
      labelFormatter: function(a) {
        let out = `<div class="${classNames("Chart__legend_item", {
          visible: this.visible
        })}" style="background: ${getCurrencyInfo(this.name).background}">${
          this.name
        }</div>`;
        if (props.adaptive) {
          out = `<div style="margin: 20px 0">
            ${out}
          </div>`;
        }
        return out;
      },
      itemMarginBottom: 0,
      margin: 0,
      x: 0,
      padding: 0,
      itemMarginTop: 16,
      align: "left",
      alignColumns: false,
      itemDistance: 16,
      states: {
        hover: {
          enabled: false
        }
      },
      itemStyle: {
        opacity: 1
      },
      itemHoverStyle: {
        opacity: 0.7
      }
    },
    credits: {
      enabled: false
    },
    plotOptions: {
      column: {
        borderWidth: 0
      },
      series: {
        lineWidth: 3,
        marker: {
          enabled: props.marker,
          radius: 3,
          symbol: "circle",
          fillColor: getCssVar("--primary-background", "#fff"),
          lineColor: null,
          lineWidth: 2
        },
        shadow: {
          enabled: false
        },
        states: {
          hover: {
            enabled: true,
            halo: {
              size: 10
            }
          }
        },
        events: {
          legendItemClick: () => {
            if (props.count < 2) {
              return false;
            }
          },
          mouseOver: function(e) {
            setHovered(true);
            if (props.count > 1) {
              this.xAxis.update({ className: "Chart__xaxis_invisible" });
            }
          },
          mouseOut: function() {
            setHovered(false);
            if (props.count > 1) {
              this.xAxis.update({ className: "" });
            }
          }
        }
      }
    },
    tooltip: {
      shared: true,
      split: true,
      useHTML: true,
      padding: 0,
      borderWidth: 0,
      shadow: false,
      followPointer: true,
      backgroundColor: "transparent",
      crosshairs: true,
      hideDelay: 0,
      formatter: function(tooltip) {
        return [
          `<div class="Chart__tooltip_date">${dateFormat(this.x, "L")}</div>`
        ].concat(
          this.points.map(point => {
            return `<div class="Chart__tooltip" style="background: ${
              getCurrencyInfo(point.series.name).background
            }">
            ${point.series.data.filter(p => p.y === point.y)[0].title}
          </div>`;
          })
        );
      }
    },

    series
  };

  return (
    <HighchartsReact
      containerProps={{ className: classNames("Chart", { hovered }) }}
      highcharts={Highcharts}
      options={options}
    />
  );
}

Chart.propTypes = {
  series: PropTypes.array
};
