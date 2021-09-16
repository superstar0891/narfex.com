import "./Ratings.less";

import React from "react";
import { Collapse, NumberFormat } from "../../../../../../ui";
import { useSelector } from "react-redux";
import cn from "classnames";
import {
  partnersRatingSelector,
  userSelector
} from "../../../../../../selectors";
import Lang from "../../../../../../components/Lang/Lang";

export default () => {
  const ratings = useSelector(partnersRatingSelector);
  const user = useSelector(userSelector);
  return (
    <Collapse
      className="PartnersRatings"
      skipCollapseOnDesktop
      title={<Lang name="cabinet_partners_topUsers" />}
    >
      <div className="PartnersRatings__content">
        <div className="PartnersRatings__labels">
          <span className="PartnersRatings__label">
            <Lang name="global_user" />
          </span>
          <span className="PartnersRatings__label">
            <Lang name="global_profit" /> (BTC)
          </span>
        </div>

        <ul className="PartnersRatings__list">
          {ratings.map(rating => {
            const isMyLogin =
              user.login?.toLowerCase() === rating.user_login.toLowerCase();
            return (
              <li
                key={rating.id}
                className={cn("PartnersRatings__user", { active: isMyLogin })}
              >
                <div className="PartnersRatings__user__place">
                  {rating.rank}
                </div>
                <div className="PartnersRatings__user__name">
                  {rating.user_login}
                  {isMyLogin && (
                    <span className="PartnersRatings__user__name__label">
                      <Lang name="cabinet_partners_topUsers_itIsYou" />
                    </span>
                  )}
                </div>
                <div className="PartnersRatings__user__profit">
                  <NumberFormat
                    color
                    symbol
                    number={rating.amount}
                    hiddenCurrency
                    currency="btc"
                  />
                </div>
              </li>
            );
          })}
        </ul>
        <div className="PartnersRatings__info">
          <Lang name="cabinet_partners_topUsers_info" />
        </div>
      </div>
    </Collapse>
  );
};
