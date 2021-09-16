import "./Roadmap.less";
import React from "react";
import Timeline from "src/landing/components/Timeline/Timeline";
import { timeLine } from "./constants";
import Lang from "src/components/Lang/Lang";

export default () => {
  return (
    <div className="LandingWrapper__block TokenRoadmap">
      <div className="LandingWrapper__content Roadmap__content">
        <h3>
          <Lang name="landingToken_roadmap_title" />
        </h3>
        <Timeline timeLine={timeLine} />
      </div>
    </div>
  );
};
