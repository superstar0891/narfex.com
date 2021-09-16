import "./Footer.less";

import React, { memo } from "react";
import { Status } from "../../../../../../ui";
import { dateFormat } from "src/utils/index";
import Lang from "../../../../../../components/Lang/Lang";

export default memo(({ status, date }) => {
  return (
    <div className="OperationModal__footer">
      {status && (
        <div className="OperationModal__footer__left">
          <div className="OperationModal__footer__label">
            <Lang name="global_status" />
          </div>
          <div className="OperationModal__footer__value">
            <Status indicator status={status} />
          </div>
        </div>
      )}
      {date && (
        <div className="OperationModal__footer__right">
          <div className="OperationModal__footer__label">
            <Lang name="global_date" />
          </div>
          <div className="OperationModal__footer__value">
            {dateFormat(date)}
          </div>
        </div>
      )}
    </div>
  );
});
