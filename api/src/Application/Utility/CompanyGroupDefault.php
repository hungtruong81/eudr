<?php

declare(strict_types=1);

namespace App\Application\Utility;


class CompanyGroupDefault
{
    // Company group: Farmer
    public static function getPermissionOfFarmer() {
        return [
            "default_name" => "farmer",
            "name" => "Farmer (Nông hộ)",
            "description" => "Nhóm Nông Hộ",
            "status" => "active",
            "permissions" => [
                "land.create",
                "land.view.self",
                "land.update.self",
                "land.delete.self",
                "plant.create",
                "plant.view.self",
                "plant.update.self",
                "plant.delete.self",
                "harvest_plan.create",
                "harvest_plan.view.self",
                "harvest_plan.update.self",
                "harvest_plan.delete.self",
                "harvest_schedule.create",
                "harvest_schedule.view.self",
                "harvest_schedule.update.self",
                "harvest_schedule.delete.self",
                "harvest_result.view.self",
                "transaction_ticket.sale.view.self",
                "transaction_ticket.sale.update.self"
            ]
        ];
    }
    // Company group: Purchaser
    public static function getPermissionOfPurchaser() {
        return [
            "default_name" => "purchaser",
            "name" => "Purchaser (Người mua)",
            "description" => "Nhóm Người Mua",
            "status" => "active",
            "permissions" => [
                "transaction_ticket.purchase.create",
                "transaction_ticket.purchase.view.self",
                "transaction_ticket.purchase.update.self",
                "transaction_ticket.purchase.delete.self",
                "transaction_ticket.sale.create",
                "transaction_ticket.sale.view.self",
                "transaction_ticket.sale.update.self",
                "transaction_ticket.sale.delete.self"
            ]
        ];
    }
    // Company group: Trader
    public static function getPermissionOfTrader() {
        return [
            "default_name" => "trader",
            "name" => "Trader (Thương lái)",
            "description" => "Nhóm Thương Lái",
            "status" => "active",
            "permissions" => [
                "transaction_ticket.purchase.create",
                "transaction_ticket.purchase.view.self",
                "transaction_ticket.purchase.update.self",
                "transaction_ticket.purchase.delete.self",
                "transaction_ticket.sale.create",
                "transaction_ticket.sale.view.self",
                "transaction_ticket.sale.update.self",
                "transaction_ticket.sale.delete.self"
            ]
        ];
    }
    // Company group: Manufacturer
    public static function getPermissionOfCompany() {
        return [
            "default_name" => "company",
            "name" => "Company (Công ty)",
            "description" => "Nhóm Công Ty Sản Xuất",
            "status" => "active",
            "permissions" => [
                "vehicle.create",
                "vehicle.view.own",
                "vehicle.update.own",
                "vehicle.delete.own",
                "company_group.create",
                "company_group.view.own",
                "company_group.update.own",
                "company_group.delete.own",
                "company_member.create",
                "company_member.view.own",
                "company_member.update.own",
                "company_member.delete.own",
                "factory.create",
                "factory.view.own",
                "factory.update.own",
                "factory.delete.own",
                "finished_goods_receipt.create",
                "finished_goods_receipt.view.own",
                "finished_goods_receipt.update.own",
                "finished_goods_receipt.delete.own",
                "production_order.create",
                "production_order.view.own",
                "production_order.update.own",
                "production_order.delete.own",
                "product_tank.create",
                "product_tank.view.own",
                "product_tank.update.own",
                "product_tank.delete.own",
                "product_type.create",
                "product_type.view.own",
                "product_type.update.own",
                "product_type.delete.own",
                "price.create",
                "price.view.own",
                "price.update.own",
                "price.delete.own",
                "raw_material_release.create",
                "raw_material_release.view.own",
                "raw_material_release.update.own",
                "raw_material_release.delete.own",
                "raw_material_tank.create",
                "raw_material_tank.view.own",
                "raw_material_tank.update.own",
                "raw_material_tank.delete.own",
                "transportation_route.create",
                "transportation_route.view.own",
                "transportation_route.update.own",
                "transportation_route.delete.own",
                "land.create",
                "land.view.own",
                "land.update.own",
                "land.delete.own",
                "plant.create",
                "plant.view.own",
                "plant.update.own",
                "plant.delete.own",
                "transaction_ticket.purchase.create",
                "transaction_ticket.purchase.view.own",
                "transaction_ticket.purchase.update.own",
                "transaction_ticket.purchase.delete.own",
                "transaction_ticket.sale.create",
                "transaction_ticket.sale.view.own",
                "transaction_ticket.sale.update.own",
                "transaction_ticket.sale.delete.own",
                "harvest_plan.create",
                "harvest_plan.view.own",
                "harvest_plan.update.own",
                "harvest_plan.delete.own",
                "harvest_schedule.create",
                "harvest_schedule.view.own",
                "harvest_schedule.update.own",
                "harvest_schedule.delete.own",
                "harvest_result.view.own",
                "harvest_result.update.own"
            ]
        ];
    }
    // Company group: Inspector
    public static function getPermissionOfInspector() {
        return [];
    }

}