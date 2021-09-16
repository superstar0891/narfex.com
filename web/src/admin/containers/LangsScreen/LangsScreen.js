import "./LangsScreen.less";

import React, { useEffect } from "react";
import { connect } from "react-redux";
import { classNames as cn } from "../../../utils";

import {
  getLangs,
  setType,
  setKeyNewValue,
  save,
  addNewKey,
  setLang,
  setFilter,
  keyDelete
} from "src/actions/admin/langs";
import {
  SwitchTabs,
  Dropdown,
  Input,
  Table,
  TableCell,
  TableColumn,
  Button
} from "src/ui/index";
import Block from "../../components/Block/Block";
import LoadingStatus from "../../../index/components/cabinet/LoadingStatus/LoadingStatus";
import { ReactComponent as TrashIcon } from "src/asset/24px/trash.svg";

const LangsScreen = ({
  setType,
  getLangs,
  langs,
  setKeyNewValue,
  addNewKey,
  save,
  langList,
  setLang,
  keyDelete,
  setFilter
}) => {
  useEffect(() => {
    getLangs();
  }, [getLangs, langs.langType, langs.lang]);

  return (
    <div className="LangsScreen">
      <Block title="Translations">
        <div className="AdminTable">
          <div className={cn("Table__search", langs.loadingStatus.default)}>
            <Input value={langs.filter} onTextChange={setFilter} />
            <Dropdown
              placeholder="Placeholder"
              value={langs.lang}
              onChangeValue={setLang}
              options={langList.map(l => ({
                title: l.title,
                value: l.value,
                note: l.value.toUpperCase()
              }))}
            />
            <SwitchTabs
              selected={langs.langType}
              onChange={setType}
              tabs={[
                { value: "web", label: "Web" },
                { value: "mobile", label: "Mobile" },
                { value: "backend", label: "Backend" }
              ]}
            />
          </div>
        </div>

        <div className="LangsScreen__table">
          <Table
            headings={[
              <TableColumn>Key</TableColumn>,
              <TableColumn>Origin</TableColumn>,
              <TableColumn>Translation</TableColumn>,
              <TableColumn className={"actionsColumn"}>Actions</TableColumn>
            ]}
          >
            {langs.keys
              .filter(k =>
                langs.filter ? k.name.includes(langs.filter) : true
              )
              .map(item => (
                <TableCell key={item.name}>
                  <TableColumn>{item.name}</TableColumn>
                  <TableColumn>{item.en_value}</TableColumn>
                  <TableColumn>
                    <Input
                      value={
                        langs.update[item.name] !== undefined
                          ? langs.update[item.name]
                          : item.value || ""
                      }
                      onTextChange={value => {
                        setKeyNewValue(item.name, value);
                      }}
                    />
                  </TableColumn>
                  <TableColumn>
                    <Button onClick={() => keyDelete(item.name)} size="middle">
                      <TrashIcon />
                    </Button>
                  </TableColumn>
                </TableCell>
              ))}
          </Table>
          <div className={"LangScreen__footer"}>
            <Button
              size="middle"
              disabled={!Object.keys(langs.update).length}
              onClick={save}
              state={langs.loadingStatus.save}
            >
              Save
            </Button>
            <Button size="middle" type="secondary" onClick={addNewKey}>
              Add new key
            </Button>
          </div>
          {langs.loadingStatus.default && (
            <div className="LangsScreen__loader">
              <LoadingStatus inline status="loading" />
            </div>
          )}
        </div>
      </Block>
    </div>
  );
};

export default connect(
  state => ({
    langs: state.langs,
    langList: state.default.langList
  }),
  {
    setFilter,
    keyDelete,
    getLangs,
    setKeyNewValue,
    addNewKey,
    setLang,
    save,
    setType
  }
)(LangsScreen);
