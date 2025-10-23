import TestController from './TestController'
import Settings from './Settings'

const Controllers = {
    TestController: Object.assign(TestController, TestController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers