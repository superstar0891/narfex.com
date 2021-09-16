import "./EmptyHistory.less";

import React from "react";
import { ContentBox } from "../../../../../../ui";

import { ReactComponent as BitcoinIcon } from "src/asset/illustrations/bitcoin.svg";

export default () => {
  return (
    <ContentBox className="ProfileEmptyHistory">
      <div className="ProfileEmptyHistory__content">
        <div className="ProfileEmptyHistory__icon">
          <BitcoinIcon />
        </div>
        <h3 className="ProfileEmptyHistory__title">Заработайте криптовалюту</h3>
        <p className="ProfileEmptyHistory__description">
          У вас есть прекрасная возможность привлечь инвестора для покупки
          Narfex Token и получить вознаграждение в зависимости от суммы его
          инвестиций.
        </p>
      </div>
    </ContentBox>
  );
};
