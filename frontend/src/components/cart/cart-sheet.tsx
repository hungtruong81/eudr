import * as React from "react";
import {
  Sheet,
  SheetClose,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Icons } from "@/components/icons";
import { Separator } from "@/components/ui/separator";
import { ScrollArea } from "@/components/ui/scroll-area";
import { useCart } from "@/providers/cart-context";
import { CartItem } from "./cart-item";
import { ShoppingCartIcon } from "lucide-react";
import { formatPrice } from "@/lib/utils";
import Link from "next/link";

export default function CartSheet() {
  const { cartItems, isCartShow, setIsCartShow, cartTotal, cartCount } =
    useCart();
  // const cartCount = cartItems.reduce((total, item) => total + item.quantity, 0);

  return (
    <Sheet open={isCartShow} onOpenChange={setIsCartShow}>
      <SheetTrigger asChild>
        <Button
          aria-label="Cart"
          variant="outline"
          size="icon"
          className="relative"
        >
          {cartCount > 0 && (
            <Badge
              variant="secondary"
              className="absolute -right-2 -top-2 g-6 w-6 h-6 rounded-full p-2"
            >
              {cartCount}
            </Badge>
          )}
          <Icons.shoppingCart className="h-4 w-4" aria-hidden="true" />
        </Button>
      </SheetTrigger>
      <SheetContent /* className="flex w-full flex-col pr-0 sm:max-w-lg" */>
        <SheetHeader>
          <SheetTitle>Giỏ hàng {cartCount > 0 && `(${cartCount})`}</SheetTitle>
          <SheetDescription>
            {cartCount > 0
              ? "Bạn sẽ điền thông tin của bạn ở bước sau."
              : "Giỏ hàng của bạn đang trống."}
          </SheetDescription>
        </SheetHeader>
        <Separator />
        {cartCount > 0 && (
          <div className="flex flex-1 flex-col gap-5 overflow-hidden">
            <ScrollArea className="h-full">
              <div className="flex flex-col gap-5 px-6">
                {cartItems.map((item) => (
                  <div key={item.product.courseId} className="space-y-3">
                    <CartItem item={item} />
                  </div>
                ))}
              </div>
            </ScrollArea>
          </div>
        )}
        {cartCount > 0 && (
          <SheetFooter>
            <Separator className="my-4" />

            <div>
              <div className="flex items-center justify-between text-lg font-semibold text-foreground">
                <p>Tạm tính</p>
                <p>{formatPrice(cartTotal)}</p>
              </div>
              <div className="text-sm text-muted-foreground">
                Chi phí khác và thuế có thể được tính lại khi thanh toán
              </div>
            </div>
            <SheetClose asChild>
              <Button
              asChild
              className="w-full mt-4"
              size="lg"
              variant="default"
              aria-label="Thanh toán"
              >
              <Link href="/thanh-toan" prefetch={false}>
                <ShoppingCartIcon className="mr-2" />
                Thanh toán {formatPrice(cartTotal)}
              </Link>
              </Button>
            </SheetClose>
            <SheetClose asChild>
              <Button variant="link">Tiếp tục xem thêm</Button>
            </SheetClose>
          </SheetFooter>
        )}
      </SheetContent>
    </Sheet>
  );
}
