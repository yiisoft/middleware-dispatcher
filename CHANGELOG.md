# Yii Middleware Dispatcher Change Log

## 5.1.0 under development

- Enh #75: Optimize `MiddlewareFactory` performance (@random-rage)
- New #76: Add composite parameters resolver (@vjik)

## 5.0.0 January 09, 2023

- New #68: Add `ParametersResolverInterface` to resolve parameters of middleware that are provided as callable (@rustamwin)
- Chg #68: Remove wrapper factory (@rustamwin)
- Enh #69: Add support for callable middlewares (@rustamwin)
- Enh #69: Add debug info to callable wrapper (@rustamwin)

## 4.0.0 November 10, 2022

- Enh #59: Raise minimum PHP version to `^8.0` (@xepozz, @vjik)
- Enh #62: Add support for `yiisoft/definitions` version `^3.0` (@vjik)

## 3.0.0 September 07, 2022

- New #55: Add wrapper factory (@rustamwin)
- Chg #56: Remove `MiddlewareFactoryInterface`, so the factory doesn't need to be implemented (@rustamwin)

## 2.1.0 August 05, 2022

- New #53: Add array definition support for middleware (@vjik)
- Enh #45: Implement friendly exception for `InvalidMiddlewareDefinitionException` (@vjik)

## 2.0.1 February 14, 2022

- Chg #47: Add debug info to action wrapper (@rustamwin)

## 2.0.0 November 10, 2021

- Chg #43: Reverse middleware order in `withMiddlewares()` (@rustamwin)

## 1.0.0 November 08, 2021

- Initial release.
