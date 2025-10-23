import TestController from './TestController'
import Settings from './Settings'
import TimetableController from './TimetableController'

const Controllers = {
    TestController: Object.assign(TestController, TestController),
    Settings: Object.assign(Settings, Settings),
    TimetableController: Object.assign(TimetableController, TimetableController),
}

export default Controllers