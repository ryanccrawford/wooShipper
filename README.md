# woocommerce-twdstore-custom-backend
Woo Commerce Custom Shipping Calculator:
The admin must add all shipping box sizes into the system using the Add Boxes menu item
Next the admin must set all the options for UPS, USPS & RL Carriers.
Make sure all API keys are entered into the backend for these carriers too.
# How's it work
1st the customers shopping cart is divided up into items.
Then the program will try and fit each item into a packing box until it finds the combination of packing items using the least amount of boxes.
Then these implemented packages, which have dimensions and weight, are then sent the Shippers API’s where rate quotes are returned. The rates are then grouped by price and presented to the customer for selection.
** Is smart enough to know if an item goes freight to send all items freight. 
