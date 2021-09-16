import "./Application.less";
import React from "react";
import AppButtons from "../../../../../components/AppButtons/AppButtons";
import Lang from "../../../../../components/Lang/Lang";
import { classNames as cn } from "src/utils";

export default ({ accent }) => {
  return (
    <div className={cn("LandingWrapper__block Application", { accent })}>
      <div className="LandingWrapper__content">
        <h2>
          <Lang name="landing_application_title" />
        </h2>
        <div className="Application__content">
          <div className="Application__image" />
          <div className="Application__description">
            <h3>
              <Lang name="landing_application_subtitle" />
            </h3>
            <p>
              <Lang name="landing_application_description" />
            </p>
            <AppButtons />
          </div>
        </div>
      </div>
    </div>
  );
};
