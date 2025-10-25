import GenerateInputController from './GenerateInputController'
import Settings from './Settings'
import TimetableController from './TimetableController'
import DBLoaderController from './DBLoaderController'

const Controllers = {
    GenerateInputController: Object.assign(GenerateInputController, GenerateInputController),
    Settings: Object.assign(Settings, Settings),
    TimetableController: Object.assign(TimetableController, TimetableController),
    DBLoaderController: Object.assign(DBLoaderController, DBLoaderController),
}

export default Controllers