import "./MethodsList.less";

import React, { useState } from "react";
import SVG from "react-inlinesvg";
import Clipboard from "../../../Clipboard/Clipboard";
import { classNames as cn } from "src/utils/index";

export default props => {
  const [openedId, setOpenedId] = useState(null);

  const toggleItem = id => {
    setOpenedId(id === openedId ? null : id);
  };

  const formatRow = (row, keys) => {
    const keysOfKeys = Object.keys(keys);
    return row.split(/({account_number}|{service_provider_code})/).map(e => {
      const key = e.slice(1, e.length - 1);
      if (keysOfKeys.includes(key)) {
        return <Clipboard skipIcon text={keys[key]} />;
      } else {
        return e;
      }
    });
  };

  return (
    <div className="MethodsList">
      {props.methods.map((method, id) => (
        <div
          className={cn("MethodsList__item", { opened: id === openedId })}
          key={id}
        >
          <div
            className="MethodsList__item__title"
            onClick={() => toggleItem(id)}
          >
            <span>{method.name}</span>
            <SVG src={require("src/asset/24px/angle-down-small.svg")} />
          </div>
          <div className="MethodsList__item__content">
            <ol className="MethodsList__steps">
              {method.steps.map((step, i) => (
                <li key={i} className="MethodsList__steps__step">
                  <div className="MethodsList__steps__step__number">
                    {i + 1}
                  </div>
                  <span>{formatRow(step, props.keys)}</span>
                </li>
              ))}
            </ol>
          </div>
        </div>
      ))}
    </div>
  );
};
