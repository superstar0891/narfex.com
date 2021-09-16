import "./Steps.less";

import React from "react";

function Steps({ stepsData }) {
  return (
    <div className="Steps">
      {stepsData.map((step, i) => (
        <div key={step.num} className="Steps__single">
          <div className="Steps__circle">
            <h4 className="Steps__circle__number">{step.num}</h4>
            {i !== 0 ? (
              <img
                src={require("./asset/step_line.svg")}
                alt="step-line"
                className="Steps__line"
              />
            ) : null}
          </div>
          <h3 className="Steps__title">{step.title}</h3>
          <p className="Steps__caption">{step.caption}</p>
        </div>
      ))}
    </div>
  );
}

export default React.memo(Steps);
