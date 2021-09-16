import "./HistoryTable.less";

import React, { memo } from "react";
import moment from "moment";

import * as UI from "src/ui";
import * as utils from "src/utils";
import { classNames as cn } from "src/utils";
import EmptyContentBlock from "src/index/components/cabinet/EmptyContentBlock/EmptyContentBlock";
import LoadingStatus from "src/index/components/cabinet/LoadingStatus/LoadingStatus";
import HistoryItemWidget from "src/index/components/cabinet/HistoryItemWidget/HistoryItemWidget";
import Lang from "../../../../components/Lang/Lang";

const formatDate = time => {
  if (time > Date.now() - 2 * 24 * 60 * 60 * 1000) {
    return moment(time).fromNow();
  } else if (new Date(time).getFullYear() === new Date().getFullYear()) {
    return utils.dateFormat(time, "DD MMMM");
  } else {
    return utils.dateFormat(time, "DD MMMM YYYY");
  }
};

export default memo(({ history, type, status, header }) => {
  const transactions = history
    .map(t => ({
      ...t,
      created_at: t.created_at || t.date, // TODO: HACK
      group: formatDate((t.created_at || t.date) * 1000)
    }))
    .reduce((r, a) => {
      r[a.group] = [...(r[a.group] || []), a];
      return r;
    }, {});

  if (status) {
    return (
      <UI.ContentBox className="HistoryTable">
        <LoadingStatus status={status} />
      </UI.ContentBox>
    );
  }

  if (!history.length) {
    return (
      <EmptyContentBlock
        icon={require("src/asset/120/clock.svg")}
        message={utils.getLang("cabinet_noFiatHistory")}
      />
    );
  }

  return (
    <UI.ContentBox className="HistoryTable">
      {header && (
        <div className="HistoryTable__header">
          <Lang name="global_operations" />
        </div>
      )}
      {Object.keys(transactions).map(key => (
        <div key={key} className="HistoryTable__group">
          <div
            className={cn("HistoryTable__group__title", {
              unread: transactions[key][0].unread
            })}
          >
            {utils.ucfirst(key)}
          </div>
          {transactions[key].map(item => (
            <HistoryItemWidget
              type={type}
              key={item.type + item.id}
              item={item}
            />
          ))}
        </div>
      ))}
    </UI.ContentBox>
  );
});
