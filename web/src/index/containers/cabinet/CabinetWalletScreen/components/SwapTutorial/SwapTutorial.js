import "./SwapTutorial.less";

import React from "react";
import { ContentBox } from "../../../../../../ui";
import Lang from "../../../../../../components/Lang/Lang";
import { openModal } from "../../../../../../actions";

export default () => {
  return (
    <ContentBox className="SwapTutorial">
      <div className="SwapTutorial__image"></div>
      <ul className="SwapTutorial__list">
        <li>
          <h4>
            <Lang name="cabinet__swapTutorial_refillBalance" />
          </h4>
          <p>
            <Lang name="cabinet__swapTutorial_refillBalance_text" />
          </p>
          <span
            className="link"
            onClick={() => {
              openModal("merchant", { currency: "idr" });
            }}
          >
            <Lang name="cabinet__swapTutorial_refillBalance_link" />
          </span>
        </li>
        <li>
          <h4>
            <Lang name="cabinet__swapTutorial_swapCurrencies" />
          </h4>
          <p>
            <Lang name="cabinet__swapTutorial_swapCurrencies_text" />
          </p>
        </li>
      </ul>
    </ContentBox>
  );
};
