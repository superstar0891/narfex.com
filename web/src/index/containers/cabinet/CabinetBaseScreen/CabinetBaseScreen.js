import BaseScreen from "../../BaseScreen";
import { setAdaptive } from "../../../../actions";
import { PHONE } from "../../../constants/breakpoints";

export default class CabinetBaseScreen extends BaseScreen {
  constructor() {
    super();
    this.handleResize();
  }

  get section() {
    return this.props.routerParams.section || "default";
  }

  get isLoading() {
    return !!this.props.loadingStatus[this.section];
  }

  get loadingStatus() {
    return this.props.loadingStatus[this.section] || "";
  }

  componentDidMount() {
    this.load();
    this.handleResize(); // TODO LEGACY
    window.addEventListener("resize", this.handleResize);
  }

  componentWillUnmount() {
    // TODO LEGACY
    window.removeEventListener("resize", this.handleResize);
  }

  handleResize() {
    // TODO LEGACY
    if (document.body.offsetWidth <= PHONE) {
      setAdaptive(true);
    } else {
      setAdaptive(false);
    }
  }

  componentWillUpdate(nextProps) {
    if (nextProps.routerParams.section !== this.props.routerParams.section) {
      this.load(nextProps.routerParams.section || "default");
    }
  }

  load() {
    // need to override
  }
}
