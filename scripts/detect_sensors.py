import ownet
from time import localtime, mktime

ownet.init('localhost:4304')

dFile = open('/etc/owfs-etm/discovered','r') #discovered file, open for reading
dAddresses = [] #already discovered addresses
newAddresses = [] #new addresses found during this run
newFile = [] #array of lines to write out to file

newFile.append('#This is an automatically generated file\n')
newFile.append('#DO NOT edit this file by hand; it may cause undefined behaviour\n')

lineList = dFile.readlines()

dFile.close()
dFile = open('/etc/owfs-etm/discovered','w') #discovered file, open for writing

for line in lineList:
    if(line.startswith('#') == True):
        continue
    newFile.append(line)
    dAddresses.append(line.split(':')[0])

for directory in ownet.Sensor('/','localhost',4304).sensorList():
    seen = False
    address = None
    if(directory == ownet.Sensor('/simultaneous','localhost',4304)):
        continue

    #If the directory contains "temperature", it is a sensor
    if hasattr(directory,'temperature'):
        #store the address
        address = directory.address
        for a in dAddresses:
            #check against every discovered addresss
            if(address == a):
                seen = True
                break
        if(seen == False):
            newAddresses.append(address)

for a in newAddresses:
    #Build a string in the form of "[address]:[timestamp]\n"
    addressLine = a + ':' + str(mktime(localtime())).split('.')[0] + '\n'
    #Append it to the new file line array
    newFile.append(addressLine)

dFile.writelines(newFile)
dFile.close()
