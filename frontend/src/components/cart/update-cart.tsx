
import React from 'react'
import { useCart } from '@/providers/cart-context'
import { CartItemConfig,  } from "@/lib/types"
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Icons } from "@/components/icons";

interface CartItemActionsProps {
  item: CartItemConfig;
}

export function CartItemActions({ item }: CartItemActionsProps) {
  const { updateCartItemQuantity, removeFromCart } = useCart();

  const handleQuantityChange = (qty: number) => {
    const quantity = Number(qty)
    if (quantity >= 1) {
      updateCartItemQuantity(item.product.courseId, quantity)
    }
  }

  const handleRemoveClick = () => {
    removeFromCart(item.product.courseId);
  };

  return (
    <div className='flex items-center space-x-1'>
      {/* <div className="flex items-center space-x-1">
        <Button variant="outline" size="icon" className='h-8 w-8' onClick={() => {
          handleQuantityChange(item.quantity - 1)
        }}>-</Button>
      </div>
      <Input
      className='h-8 w-14 text-xs'
        type="number"
        min="1"
        value={item.quantity}
        onChange={(e) => {
          handleQuantityChange(Number(e.target.value))
        }}
      />
      <Button variant="outline" size="icon" className='h-8 w-8' onClick={() => {
          handleQuantityChange(item.quantity + 1)
        }}>+</Button> */}
      <Button variant="outline" size="icon" className='h-8 w-8' onClick={handleRemoveClick}><Icons.trash className='h-4 w-4'/></Button>
    </div>
  );
}
