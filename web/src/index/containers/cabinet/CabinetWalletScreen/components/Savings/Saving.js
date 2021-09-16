import "./Saving.less";

import React from "react";
import { ContentBox } from "src/ui";
import { Button } from "src/ui";
import Lang from "../../../../../../components/Lang/Lang";
import * as actions from "../../../../../../actions";

export default props => {
  return (
    <ContentBox className="SavingsBlock">
      <h3>
        <Lang name="cabinet_saving_title" />
      </h3>
      <div className="SavingBlock__content">
        <p>
          <Lang name="cabinet_saving_description" />
        </p>
        <Button
          onClick={() => {
            actions.openStateModal("savings");
          }}
        >
          <Lang name="cabinet_saving_actionButton" />
        </Button>
      </div>
    </ContentBox>
  );
};
