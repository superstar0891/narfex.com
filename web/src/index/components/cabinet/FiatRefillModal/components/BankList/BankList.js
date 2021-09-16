import "./BankList.less";
import BankLogo from "../../../../../../ui/components/BankLogo/BankLogo";

import React from "react";
import SVG from "react-inlinesvg";
import LoadingStatus from "../../../LoadingStatus/LoadingStatus";
import Lang from "src/components/Lang/Lang";

export default props => {
  const handleSelect = name => () => {
    props.onChange(name);
  };

  return (
    <div className="BankList">
      {!!props.items?.length ? (
        props.items.map(bank => (
          <div className="BankList__item" onClick={handleSelect(bank)}>
            <div className="BankList__item__title">{bank.name}</div>
            <BankLogo className="BankList__item__logo" name={bank.code} />
            <div className="BankList__item__arrow">
              <SVG src={require("src/asset/24px/angle-right.svg")} />
            </div>
          </div>
        ))
      ) : (
        <LoadingStatus
          icon={require("src/asset/120/error.svg")}
          status={<Lang name="cabinet_fiatWallet_noBanksAvailable" />}
        />
      )}
    </div>
  );
};
