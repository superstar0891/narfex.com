import "./Timeline.less";

import React from "react";

function Timeline({ timelineData }) {
  return (
    <div className="Timeline">
      <div className="Timeline__line" />

      {timelineData.map((event, i) => (
        <div
          key={event.title}
          className={"Timeline__event " + (i % 2 === 1 ? "left" : "right")}
        >
          <div className="Timeline__circle" />
          <h3 className="Timeline__event__title">{event.title}</h3>
          <p className="Timeline__event__caption">{event.description}</p>
        </div>
      ))}
    </div>
  );
}

export default React.memo(Timeline);
