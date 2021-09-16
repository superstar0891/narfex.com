import "./Advantages.less";

import React from "react";
import SVG from "react-inlinesvg";
import Lang from "../../../../../components/Lang/Lang";
import { classNames as cn } from "utils";
import { Link } from "react-router5";

export default ({ accent, type, mode, items, titleLang }) => {
  return (
    <div
      className={cn("Advantages LandingWrapper__block", type, mode, { accent })}
    >
      <div className="LandingWrapper__content Advantages__content">
        {titleLang && (
          <h2>
            <Lang name={titleLang} />
          </h2>
        )}
        <ul>
          {items &&
            items.map((i, key) => (
              <li key={key}>
                <SVG src={i.icon} />
                <div className="Advantages__item__text">
                  <h4>
                    <Lang name={i.titleLang} />
                  </h4>
                  <p>
                    <Lang name={i.textLang} />
                  </p>
                  {i.linkLang && (
                    <Link routeName={i.routeName}>
                      <Lang name={i.linkLang} />
                    </Link>
                  )}
                </div>
              </li>
            ))}
        </ul>
      </div>
    </div>
  );
};
