import "./Stages.less";

import React from "react";
import cn from "classnames";
import { useSelector } from "react-redux";
import {
  tokenPeriodsSelector,
  tokenCurrentPeriodIdSelector,
  tokenCurrentPeriodSelector
} from "../../../../../../selectors";
import { NumberFormat } from "../../../../../../ui";
import { dateFormat } from "src/utils/index";
import Lang from "../../../../../../components/Lang/Lang";

export default () => {
  const periods = useSelector(tokenPeriodsSelector);
  const currentPeriodId = useSelector(tokenCurrentPeriodIdSelector);

  return (
    <div className="CabinetTokenScreen__stages">
      <h3>
        <Lang name="cabinet_token_stagesTitle" />
      </h3>

      <ul className="CabinetTokenScreen__stages__list">
        {periods.map((p, key) => (
          <li
            key={key}
            className={cn("CabinetTokenScreen__stages__list__item", {
              active: key === currentPeriodId,
              old: currentPeriodId > key
            })}
          >
            <strong>
              <Lang
                name="cabinet_token_stageNumber"
                params={{ number: key + 1 }}
              />
            </strong>
            <p>
              <NumberFormat number={p.bank} currency="fndr" />
              <br />
              <Lang name="global_bonus" />{" "}
              <NumberFormat number={p.percent} percent />
            </p>
            <small>
              {[
                dateFormat(p.from, "DD MMMM"),
                dateFormat(p.to, "DD MMMM YYYY")
              ].join(" – ")}
            </small>
          </li>
        ))}
        <li
          className={cn("CabinetTokenScreen__stages__list__item", {
            active: currentPeriodId === 3
          })}
        >
          <strong>
            <Lang name="cabinet_token_icoListing" />
          </strong>
          <small>{dateFormat(1607990400, "DD MMMM YYYY")}</small>
        </li>
      </ul>
    </div>
  );
};
