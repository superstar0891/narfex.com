import React from "react";
import hexToRgba from "hex-to-rgba";
import ChartSimple from "../../../../../index/components/cabinet/Chart/ChartSimple";

export default ({ currency, chart }) => {
  const series = {
    color: currency.color,
    shadow: {
      color: currency.color
    },
    fillColor: {
      linearGradient: {
        x1: 0,
        x2: 0,
        y1: 0,
        y2: 1
      },
      stops: [
        [0, hexToRgba(currency.color, 0.4)],
        [1, hexToRgba(currency.color, 0)]
      ]
    },
    data: chart.map(([x, y]) => ({ x, y })),
    threshold: Math.min(...chart.map(d => d[1]))
  };

  return (
    <ChartSimple
      height={40}
      lineWidth={1}
      type="area"
      marker={false}
      series={[series]}
    />
  );
};
